<?php
// install_profile might be different.
$settings['install_profile'] = 'standard';
$config_directories['sync'] = '../config/sync';
$settings['hash_salt'] = getenv('HASH_SALT');

/** 
 * Collect external service information from environment. 
 * Cloud Foundry places all service credentials in VCAP_SERVICES
 */

$cf_service_data = json_decode($_ENV['VCAP_SERVICES'], true);

$db_services = array();

foreach($cf_service_data as $service_provider => $service_list) {
  foreach ($service_list as $service) {
    if (preg_match('/^mysql2?:/', $service['credentials']['uri'])) {
      $db_services[] = $service;
      continue;  // Delete this when you're sure it's not needed
    }
  }
}

// Configure Drupal, using the first database found
$databases['default']['default'] = array (
  'database' => $db_services[0]['credentials']['db_name'],
  'username' => $db_services[0]['credentials']['username'],
  'password' => $db_services[0]['credentials']['password'],
  'prefix' => '',
  'host' => $db_services[0]['credentials']['host'],
  'port' => $db_services[0]['credentials']['port'],
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

/**
 * Flysystem.
 *
 * The settings below are for configuring flysystem backends
 */
$s3_endpoint = (isset($_ENV['AWS_S3_ENDPOINT']) ? $_ENV['AWS_S3_ENDPOINT'] : "s3.amazonaws.com");
$s3_services = array();
foreach($cf_service_data as $service_provider => $service_list) {
  foreach ($service_list as $service) {
    // looks for tags of 's3'
    if (in_array('S3', $service['tags'], true)) {
      $s3_services[] = $service;
      continue;
    }
    // look for a service where the name includes 's3'
    if (strpos($service['name'], 'S3') !== false) {
      $s3_services[] = $service;
    }
  }
}

$settings['flysystem']['s3'] = array(
  'driver' => 's3',
  'config' => array(
    'key'    => $s3_services[0]['credentials']['access_key_id'],
    'secret' => $s3_services[0]['credentials']['secret_access_key'],
    'region' => $s3_services[0]['credentials']['region'],
    'bucket' => $s3_services[0]['credentials']['bucket'],
    // Optional configuration settings.
    'options' => array(
      'ACL' => 'public-read',
      'StorageClass' => 'REDUCED_REDUNDANCY',
    ),
    'protocol' => 'https',      // Will be autodetected based on the current request.
    'prefix' => 'flysystem-s3', // Directory prefix for all uploaded/viewed files.
    'cname' => $s3_endpoint,
    'endpoint' => "https://$s3_endpoint"
  ),
  'cache' => TRUE, // Creates a metadata cache to speed up lookups.
);

// CSS and JS aggregation need per dyno/container cache.
// This is from https://www.fomfus.com/articles/how-to-create-a-drupal-8-project-for-heroku-part-1
// included here without fully understanding implications:
$settings['cache']['bins']['data'] = 'cache.backend.php';