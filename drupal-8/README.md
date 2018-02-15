# Working with Drupal8 in cloud.gov (and Cloud Foundry)

[Drupal](https://drupal.org) has been a popular PHP-based framework for content-management systems and web applications. It was not written as a cloud-native framework, so it takes a bit of tweaking to run on cloud.gov, or any other Cloud Foundry implementation. If you're on a _greenfield_ project you may want to consider other PHP frameworks, but if you're going forward with Drupal then this guide will get you there.

This guide is written for [cloud.gov](https://cloud.gov/) users, but will work for any Cloud Foundry site. Just replace the specifics for `aws-rds` and `s3` with your site equivalents and everything should just work. 

## Quickstart

You can demonstrate a fully-functional Drupal install assuming you've already cloned this repo, and are using this directory:

Update `manifest.yml` with:

1. the correct admin name, `ACCOUNT_NAME`
1. delete or change the `AWS_S3_ENDPOINT` if you're _not_ on cloud.gov

Log in to Cloud Foundry (e.g. `cf login --sso -a https://api.fr.cloud.gov`), then run the following commands for cloud.gov:

```
cf create-service s3 basic-public d8ex-s3
cf create-service aws-rds shared-mysql d8ex-db
cf push d8ex --no-start -b  https://github.com/cloudfoundry/apt-buildpack.git
# Set the ACCOUNT_PASS as an environment variable, or it'll be auto-generated and recorded in the logs
cf set-env d8ex ACCOUNT_PASS "your-account-pass"
cf v3-push d8ex -b https://github.com/cloudfoundry/apt-buildpack.git -b php_buildpack 
```

This project uses the "multi-buildpack" feature of Cloud Foundry, since we need `apt` to install the `mysql` client, and the `php_buildpack` for Drupal itself. The `v3-push` is experimental, so the syntax and usage may change.


When the `v3-push` command completes:
- If you didn't set ACCOUNT_PASS, then use `cf logs d8ex --recent | grep ACCOUNT_PASS` to determine the password
- Visit the site URL
- Login with `ACCOUNT_NAME` and `ACCOUNT_PASS`
- Set up use of S3 Flysystem instead of local disk:
  - As a admin, go to Configuration -> File system, and set "Default download method" `Flysystem: s3`
- On a default Drupal install, there should be two fields using the local filesystem. Those fields need to be updated to use Amazon S3:
  - Image field on Article content type (Structure > Content types > Article > Manage fields > Image)
  - Image field for User profile picture (Configuration > People > Account settings > Manage fields > Picture)


You are all set to use Drupal with Cloud Foundry\*. Congratulations!


\*: see "Known Issues", below


## Gotchas:

1. `'v3-push' is not a registered command. See 'cf help'` : You'll need to update your CF CLI install.


----
# Building your own Drupal project

This project demonstrates a Drupal project initiated with `composer`. Let's step through the process so you can repeat for your Drupal project. The steps are for MacOS. 

Install composer:
```
brew install homebrew/php/composer
```

Create a fresh Drupal8 project named `d8ex`:
```
composer create-project drupal-composer/drupal-project:8.x-dev d8ex --stability dev --no-interaction
cd d8ex
composer require drupal/flysystem_s3
```

Check your work into Git:
```
git init
git add .
git commit -m "Initial commit from composer"
```


Customize for Cloud Foundry by copying the following to your project
* Copy the `.bp-config/` directory to your project
* `.cfignore`
* `.gitignore` (we don't ignore the `settings.php` file)
* `apt.yml`
* `bootstrap.sh`
* `manifest.yml`


E.g., if you've cloned this project to $HOME/Projects/18f/cf-ex-drupal/, then:

```
SOURCE_DRUPAL=$HOME/Projects/18f/cf-ex-drupal/drupal-8/
cp -r $SOURCE_DRUPAL/.bp-config .
git add .bp-config
for f in .cfignore .gitignore apt.yml bootstrap.sh manifest.yml; do
  cp $SOURCE_DRUPAL/$f $f
  git add $f
done
```

Add the service parsing to `settings.php` by pasting in the following:
```
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
```

Now you can push as you did above:


# Debugging

## SSH Setup

If you `cf ssh` to the application, you'll need to set the following variables to work effectively with the DB or PHP:

```
export HOME=/home/vcap/app
export DEPS_DIR=/home/vcap/deps
export PYTHONPATH=/home/vcap/app/.bp/lib
export TMPDIR=/home/vcap/tmp
export LIBRARY_PATH=/home/vcap/deps/0/lib
export PHPRC=/home/vcap/app/php/etc
export PHP_INI_SCAN_DIR=/home/vcap/app/php/etc/php.ini.d
export LD_LIBRARY_PATH=/home/vcap/deps/0/lib:/home/vcap/app/php/lib
export PATH=/home/vcap/deps/0/bin:/usr/local/bin:/usr/bin:/bin:/home/vcap/app/php/bin:/home/vcap/app/php/sbin
```

## Development notes

Use a dedicated mysql DB so it's easier to clean up without having to reprovision:


# References

These are complementary to what's described here for running on Heroku

https://www.fomfus.com/articles/how-to-create-a-drupal-8-project-for-heroku-part-1
https://www.fomfus.com/articles/how-to-deploy-a-drupal-8-project-to-heroku-part-2


# Known issues

- [x] Flysystem s3 needs testing
- [ ] HASH SALT not set yet
- [ ] Install with standard profile instead of minimal
- [ ] Needs testing in terms of fresh install from composer
- [x] Determine if `apt` buildpack is still necessary with `drupal-console`, as it may use PHP libraries instead of the mysql command line. 
  - ~Answer: not needed~ Still need mysql for `drush`, even though it's not needed for `drupal site-install`
- [ ] Drupal install requires more than 256MB; need to drop memory limit from 512MB on install back to default 128MB.
- [ ] Install needs manual intervention to enable S3 storage
- [ ] Install has Error: "Your sites/default/settings.php file must define the $config_directories variable as an array containing the names of directories in which configuration files can be found. It must contain a sync key."
- [ ] Install has Error: "The trusted_host_patterns setting is not configured in settings.php. This can lead to security vulnerabilities. It is highly recommended that you configure this. See Protecting against HTTP HOST Header attacks for more information."
- [ ] Install has Warning: "PHP OPcode caching can improve your site's performance considerably. It is highly recommended to have OPcache installed on your server."
- [ ] Handling of ADMIN_PASS: Change to require the env var setting, or fail; or write to local FS and get via `cf ssh`

