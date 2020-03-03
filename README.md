## CloudFoundry PHP Example Application: Drupal 

This is an example application which can be run on CloudFoundry using the [PHP Build Pack].

This is an out-of-the-box implementation of Drupal.  It's an example of how common PHP applications can easily be run on CloudFoundry.

### Usage

1. Download the latest version of Drupal from [the project site](https://www.drupal.org/download) (get the tar.gz download).

1. Extract all of the files from Drupal to the `htdocs` directory. For example: `tar -zx --strip-components=1 -C htdocs/ -f ~/Downloads/drupal-8.8.2.tar.gz`. When done, you should have an `htdocs` directory that looks like this...

  ```bash
  drwxr-xr-x  27 piccolo  wheel     864 Mar  3 13:42 .
  drwxr-xr-x  11 piccolo  wheel     352 Mar  3 13:41 ..
  -rw-r--r--@  1 piccolo  wheel    1025 Feb  1 18:15 .csslintrc
  -rw-r--r--@  1 piccolo  wheel     357 Feb  1 18:15 .editorconfig
  -rw-r--r--@  1 piccolo  wheel     151 Feb  1 18:15 .eslintignore
  -rw-r--r--@  1 piccolo  wheel      41 Feb  1 18:15 .eslintrc.json
  -rw-r--r--   1 piccolo  wheel       0 Mar  3 13:41 .git_placeholder
  -rw-r--r--@  1 piccolo  wheel    3858 Feb  1 18:15 .gitattributes
  -rw-r--r--@  1 piccolo  wheel    2314 Feb  1 18:15 .ht.router.php
  -rw-r--r--@  1 piccolo  wheel    7878 Feb  1 18:15 .htaccess
  -rw-r--r--@  1 piccolo  wheel      95 Feb  1 18:15 INSTALL.txt
  -rw-r--r--@  1 piccolo  wheel   18092 Nov 16  2016 LICENSE.txt
  -rw-r--r--@  1 piccolo  wheel    5889 Feb  1 18:15 README.txt
  -rw-r--r--@  1 piccolo  wheel     313 Feb  1 18:15 autoload.php
  -rw-r--r--@  1 piccolo  wheel    2796 Feb  1 18:12 composer.json
  -rw-r--r--@  1 piccolo  wheel  134184 Feb  1 18:12 composer.lock
  drwxr-xr-x@ 46 piccolo  wheel    1472 Feb  1 18:15 core
  -rw-r--r--@  1 piccolo  wheel    1507 Feb  1 18:15 example.gitignore
  -rw-r--r--@  1 piccolo  wheel     549 Feb  1 18:15 index.php
  drwxr-xr-x@  3 piccolo  wheel      96 Feb  1 18:15 modules
  drwxr-xr-x@  3 piccolo  wheel      96 Feb  1 18:15 profiles
  -rw-r--r--@  1 piccolo  wheel    1594 Feb  1 18:15 robots.txt
  drwxr-xr-x@  7 piccolo  wheel     224 Feb  1 18:15 sites
  drwxr-xr-x@  3 piccolo  wheel      96 Feb  1 18:15 themes
  -rw-r--r--@  1 piccolo  wheel     848 Feb  1 18:15 update.php
  drwxr-xr-x@ 23 piccolo  wheel     736 Feb  1 18:15 vendor
  -rw-r--r--@  1 piccolo  wheel    4566 Feb  1 18:15 web.config
  ```

1.  If you don't have one already, create a MySQL service.  With Pivotal Web Services, the following command will create a free MySQL database through [ClearDb].  Any MySQL provider should work.

  ```bash
  cf create-service cleardb spark mysql
  ```

1. Copy `sites/default/default.settings.php` to `sites/default/settings.php`.

1. Edit `sites/default/settings.php` and change the value of `$settings['hash_salt'] = '';` to a random value you create. This should be unique for every installation.  

1. Also in `sites/default/settings.php`, add the following code to configure a database connection. It should be added directly below the line: `$databases = [];`.

  ```php
  /*
  * Read MySQL service properties from 'VCAP_SERVICES' env variable
  */
  $service_blob = json_decode(getenv('VCAP_SERVICES'), true);
  $mysql_services = array();
  foreach($service_blob as $service_provider => $service_list) {
      // looks for 'cleardb' or 'p-mysql' service
      if ($service_provider === 'cleardb' || $service_provider === 'p-mysql') {
          foreach($service_list as $mysql_service) {
              $mysql_services[] = $mysql_service;
          }
          continue;
      }
      foreach ($service_list as $some_service) {
          // looks for tags of 'mysql'
          if (in_array('mysql', $some_service['tags'], true)) {
              $mysql_services[] = $some_service;
              continue;
          }
          // look for a service where the name includes 'mysql'
          if (strpos($some_service['name'], 'mysql') !== false) {
              $mysql_services[] = $some_service;
          }
      }
  }

  // Configure Drupal, using the first database found
  $databases['default']['default'] = array(
      'driver' => 'mysql',
      'database' => $mysql_services[0]['credentials']['name'],
      'username' => $mysql_services[0]['credentials']['username'],
      'password' => $mysql_services[0]['credentials']['password'],
      'host' => $mysql_services[0]['credentials']['hostname'],
      'prefix' => 'drupal_',
  );
  ```

1. Optionally edit any other settings in `settings.php`. For more details on manually installing Drupal, see [here](https://www.drupal.org/docs/7/install/step-3-create-settingsphp-and-the-files-directory). Save and close when you are finished.

1. Push it to CloudFoundry.

  ```bash
  cf push
  ```

1. On your first push, you'll need to access the install script.  It'll be `http://<your-host-name>.cfapps.io/install.php`.  Follow instructions there to complete the install.  After it's done, you'll be all set.

### Caution

Please read the following before using Drupal in production on CloudFoundry.

1. Drupal is designed to write to the local file system.  This does not work well with CloudFoundry, as an application's [local storage on CloudFoundry] is ephemeral.  In other words, Drupal will write things to the local disk and they will eventually disappear.  

  You can work around this in some cases, like with media, by using a storage service like Amazon S3 or CloudFront.  However there may be other cases where Drupal or Drupal plugins try to write to the disk, so test your installation carefully.

1. This is not an issue with Drupal specifically, but PHP stores session information to the local disk.  As mentioned previously, the local disk for an application on CloudFoundry is ephemeral, so it is possible for you to lose session and session data.  If you need reliable session storage, look at storing session data in an SQL database or with a NoSQL service.


[PHP Buildpack]:https://github.com/cloudfoundry/php-buildpack
[ClearDb]:https://www.cleardb.com/
[local storage on CloudFoundry]:http://docs.cloudfoundry.org/devguide/deploy-apps/prepare-to-deploy.html#filesystem
