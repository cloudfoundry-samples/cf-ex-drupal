<?php

/**
 * @file
 * Drupal site-specific configuration file.
 * 
 * IMPORTANT CLOUD.GOV NOTE:
 * Since we've written this specifically for cloud.gov, we've stripped away 
 * default comments. See default.settings.php for all that good stuff
 *
 * IMPORTANT NOTE:
 * This file may have been set to read-only by the Drupal installation program.
 * If you make changes to this file, be sure to protect it again after making
 * your modifications. Failure to remove write permissions to this file is a
 * security risk.
 */

/**
 * Collect external service information from environment.
 *
 * Cloud Foundry places all service credentials in VCAP_SERVICES
 */
$cf_service_data = json_decode($_ENV['VCAP_SERVICES'], true);

/**
 * Database settings:
 *
 * The $databases array specifies the database connection or
 * connections that Drupal may use.  Drupal is able to connect
 * to multiple databases, including multiple types of databases,
 * during the same request.
 *
 * One example of the simplest connection array is shown below. To use the
 * sample settings, copy and uncomment the code below between the @code and
 * @endcode lines and paste it after the $databases declaration. You will need
 * to replace the database username and password and possibly the host and port
 * with the appropriate credentials for your database system.
 *
 * The next section describes how to customize the $databases array for more
 * specific needs.
 *
 * @code
 * $databases['default']['default'] = array (
 *   'database' => 'databasename',
 *   'username' => 'sqlusername',
 *   'password' => 'sqlpassword',
 *   'host' => 'localhost',
 *   'port' => '3306',
 *   'driver' => 'mysql',
 *   'prefix' => '',
 *   'collation' => 'utf8mb4_general_ci',
 * );
 * @endcode
 */
$db_services = array();

foreach($cf_service_data as $service_provider => $service_list) {
  foreach ($service_list as $service) {
    // looks for tags of 'mysql'
    if (in_array('mysql', $service['tags'], true)) {
      $db_services[] = $service;
      continue;
    }
  }
}

// Configure Drupal, using the first database found
$databases['default']['default'] = array(
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
 * Location of the site configuration files.
 *
 * The $config_directories array specifies the location of file system
 * directories used for configuration data. On install, the "sync" directory is
 * created. This is used for configuration imports. The "active" directory is
 * not created by default since the default storage for active configuration is
 * the database rather than the file system. (This can be changed. See "Active
 * configuration settings" below).
 *
 * The default location for the "sync" directory is inside a randomly-named
 * directory in the public files path. The setting below allows you to override
 * the "sync" location.
 *
 * If you use files for the "active" configuration, you can tell the
 * Configuration system where this directory is located by adding an entry with
 * array key CONFIG_ACTIVE_DIRECTORY.
 *
 * Example:
 * @code
 *   $config_directories = array(
 *     CONFIG_SYNC_DIRECTORY => '/directory/outside/webroot',
 *   );
 * @endcode
 */
$config_directories = array();

# TODO: reinstate from project-planner if needed: 
# note that the __DIR__ pattern has been updated at drupal.org to
# generally use `$app_root . '/' . $site_path . '/settings.local.php'`
# $config_directories['sync'] = __DIR__ . '/../../../config';

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

/**
 * The active installation profile.
 *
 * Changing this after installation is not recommended as it changes which
 * directories are scanned during extension discovery. If this is set prior to
 * installation this value will be rewritten according to the profile selected
 * by the user.
 *
 * @see install_select_profile()
 */
$settings['install_profile'] = 'minimal';

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 *
 * This variable will be set to a random value by the installer. All one-time
 * login links will be invalidated if the value is changed. Note that if your
 * site is deployed on a cluster of web servers, you must ensure that this
 * variable has the same value on each server.
 *
 * For enhanced security, you may set this variable to the contents of a file
 * outside your document root; you should also ensure that this file is not
 * stored with backups of your database.
 *
 * Example:
 * @code
 *   $settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
$settings['hash_salt'] = 'foobar';

/** 
* TODO: reinstate from project-planner if needed:# 
* note that the __DIR__ pattern has been updated at drupal.org to
* generally use `$app_root . '/' . $site_path . '/settings.local.php'`
*/
# $settings['hash_salt'] = file_get_contents(__DIR__ . '/../../../private/hash_salt.txt');

/**
 * Deployment identifier.
 *
 * Drupal's dependency injection container will be automatically invalidated and
 * rebuilt when the Drupal core version changes. When updating contributed or
 * custom code that changes the container, changing this identifier will also
 * allow the container to be invalidated as soon as code is deployed.
 */
$settings['deployment_identifier'] = \Drupal::VERSION;

/**
 * Access control for update.php script.
 *
 * If you are updating your Drupal installation using the update.php script but
 * are not logged in using either an account with the "Administer software
 * updates" permission or the site maintenance account (the account that was
 * created during installation), you will need to modify the access check
 * statement below. Change the FALSE to a TRUE to disable the access check.
 * After finishing the upgrade, be sure to open this file again and change the
 * TRUE back to a FALSE!
 */
$settings['update_free_access'] = FALSE;

/**
 * Authorized file system operations:
 *
 * The Update Manager module included with Drupal provides a mechanism for
 * site administrators to securely install missing updates for the site
 * directly through the web user interface. On securely-configured servers,
 * the Update manager will require the administrator to provide SSH or FTP
 * credentials before allowing the installation to proceed; this allows the
 * site to update the new files as the user who owns all the Drupal files,
 * instead of as the user the webserver is running as. On servers where the
 * webserver user is itself the owner of the Drupal files, the administrator
 * will not be prompted for SSH or FTP credentials (note that these server
 * setups are common on shared hosting, but are inherently insecure).
 *
 * Some sites might wish to disable the above functionality, and only update
 * the code directly via SSH or FTP themselves. This setting completely
 * disables all functionality related to these authorized file operations.
 *
 * @see https://www.drupal.org/node/244924
 *
 * Remove the leading hash signs to disable.
 */
$settings['allow_authorize_operations'] = FALSE;

/**
 * Default mode for directories and files written by Drupal.
 *
 * Value should be in PHP Octal Notation, with leading zero.
 */
$settings['file_chmod_directory'] = 0775;
$settings['file_chmod_file'] = 0664;

/**
 * Public file path:
 *
 * A local file system path where public files will be stored. This directory
 * must exist and be writable by Drupal. This directory must be relative to
 * the Drupal installation directory and be accessible over the web.
 */
$settings['file_public_path'] = 'sites/default/files';

/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
$settings['file_private_path'] = '../private';

/**
 * A custom theme for the offline page:
 *
 * This applies when the site is explicitly set to maintenance mode through the
 * administration page or when the database is inactive due to an error.
 * The template file should also be copied into the theme. It is located inside
 * 'core/modules/system/templates/maintenance-page.html.twig'.
 *
 * Note: This setting does not apply to installation and update pages.
 */
$settings['maintenance_theme'] = 'bartik';

/**
 * PHP settings:
 *
 * To see what PHP settings are possible, including whether they can be set at
 * runtime (by using ini_set()), read the PHP documentation:
 * http://php.net/manual/ini.list.php
 * See \Drupal\Core\DrupalKernel::bootEnvironment() for required runtime
 * settings and the .htaccess file for non-runtime settings.
 * Settings defined there should not be duplicated here so as to avoid conflict
 * issues.
 */
ini_set('memory_limit', '256M');
ini_set ('max_execution_time', 1200);
ini_set('date.timezone', 'America/New_York');


/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';


/**
 * Trusted host configuration.
 *
 * Drupal core can use the Symfony trusted host mechanism to prevent HTTP Host
 * header spoofing.
 *
 * To enable the trusted host mechanism, you enable your allowable hosts
 * in $settings['trusted_host_patterns']. This should be an array of regular
 * expression patterns, without delimiters, representing the hosts you would
 * like to allow.
 *
 * For example:
 * @code
 * $settings['trusted_host_patterns'] = array(
 *   '^www\.example\.com$',
 * );
 * @endcode
 * will allow the site to only run from www.example.com.
 *
 * If you are running multisite, or if you are running your site from
 * different domain names (eg, you don't redirect http://www.example.com to
 * http://example.com), you should specify all of the host patterns that are
 * allowed by your site.
 *
 * For example:
 * @code
 * $settings['trusted_host_patterns'] = array(
 *   '^example\.com$',
 *   '^.+\.example\.com$',
 *   '^example\.org$',
 *   '^.+\.example\.org$',
 * );
 * @endcode
 * will allow the site to run off of all variants of example.com and
 * example.org, with all subdomains included.
 */
$settings['trusted_host_patterns'] = array(
 '^.+\.app\.cloud\.gov$',
 '^.+\.local$',
 '^localhost$'
);


/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 * By default ignore node_modules and bower_components folders to avoid issues
 * with common frontend tools and recursive scanning of directories looking for
 * extensions.
 *
 * @see file_scan_directory()
 * @see \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory()
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * The default number of entities to update in a batch process.
 *
 * This is used by update and post-update functions that need to go through and
 * change all the entities on a site, so it is useful to increase this number
 * if your hosting configuration (i.e. RAM allocation, CPU speed) allows for a
 * larger number of entities to be processed in a single batch run.
 */
$settings['entity_update_batch_size'] = 50;

/**
 * Load local development override configuration, if available.
 *
 * Use settings.local.php to override variables on secondary (staging,
 * development, etc) installations of this site. Typically used to disable
 * caching, JavaScript/CSS compression, re-routing of outgoing emails, and
 * other things that should not happen on development and testing sites.
 *
 * Keep this code block at the end of this file to take full effect.
 */
#
# if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
#   include $app_root . '/' . $site_path . '/settings.local.php';
# }
