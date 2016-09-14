## CloudFoundry PHP Example Application: Drupal 

This is an example application which can be run on CloudFoundry using the [PHP Build Pack].

This is an out-of-the-box implementation of Drupal.  It's an example of how common PHP applications can easily be run on CloudFoundry.

### Usage

1. Clone the app (i.e. this repo).

  ```bash
  git clone https://github.com/cloudfoundry-samples/cf-ex-drupal.git cf-ex-drupal
  cd cf-ex-drupal
  ```

1.  If you don't have one already, create a MySQL service.  With Pivotal Web Services, the following command will create a free MySQL database through [ClearDb].  Any MySQL provider should work.

  ```bash
  cf create-service cleardb spark mysql
  ```

1. Edit `sites/default/settings.php` and change the `drupal_hash_salt`.  This should be uniqe for every installation.  Optionally edit any other settings, however you do *not* need to edit the database configuration.  The file included with this example will automatically pull that information from `VCAP_SERVICES`.

1. Push it to CloudFoundry.

  ```bash
  cf push
  ```

1. On your first push, you'll need to access the install script.  It'll be `http://<your-host-name>.cfapps.io/install.php`.  Follow instructions there to complete the install.  After it's done, you'll be all set.


### How It Works

When you push the application here's what happens.

1. The local bits are pushed to your target.  This is small, five files around 25k. It includes the changes we made and a build pack extension for Drupal.
1. The server downloads the [PHP Build Pack] and runs it.  This installs HTTPD and PHP.
1. The build pack sees the extension that we pushed and runs it.  The extension downloads the stock Drupal file from their server, unzips it and installs it into the `htdocs` directory.  It then copies the rest of the files that we pushed and replaces the default Drupal files with them.  In this case, it's just the `sites/default/settings.php` file.
1. At this point, the build pack is done and CF runs our droplet.


### Changes

1. I include a [custom list of HTTPD modules](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.bp-config/httpd/extra/httpd-modules.conf#L15).  These are the same as the default, but I've added `mod_access_compat`, which is necessary because Drupal's `.htaccess` file still uses HTTPD 2.2 config.

1. I [add the PHP extensions](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.bp-config/options.json#L2) that are needed by Drupal.

1. I add a [custom build pack extension](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.extensions/drupal/extension.py), which downloads Drupal on the remote server.  This is not strictly necessary, but it saves me from having to upload a lot of files on each push.  The version of Drupal that will be installed is [here](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.extensions/drupal/extension.py#L15).

1. I include a [copy of the default settings from the standard Drupal install](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/htdocs/sites/default/settings.php).  This is [modified](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/htdocs/sites/default/settings.php#L216-L251) to pull the database configuration from the `VCAP_SERVICES` environment variable, which is populated with information from services that are bound to the app.  Since we bind a MySQL service to our app in the instructions above, we search for that and automatically configure it for use with Drupal.

### Caution

Please read the following before using Drupal in production on CloudFoundry.

1. Drupal is designed to write to the local file system.  This does not work well with CloudFoundry, as an application's [local storage on CloudFoundry] is ephemeral.  In other words, Drupal will write things to the local disk and they will eventually disappear.  

  You can work around this in some cases, like with media, by using a storage service like Amazon S3 or CloudFront.  However there may be other cases where Drupal or Drupal plugins try to write to the disk, so test your installation carefully.

1. This is not an issue with Drupal specifically, but PHP stores session information to the local disk.  As mentioned previously, the local disk for an application on CloudFoundry is ephemeral, so it is possible for you to lose session and session data.  If you need reliable session storage, look at storing session data in an SQL database or with a NoSQL service.


[PHP Buildpack]:https://github.com/cloudfoundry/php-buildpack
[ClearDb]:https://www.cleardb.com/
[local storage on CloudFoundry]:http://docs.cloudfoundry.org/devguide/deploy-apps/prepare-to-deploy.html#filesystem
