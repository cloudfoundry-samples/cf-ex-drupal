## Cloud Foundry example application: Drupal 7

This is an example application that can be run on [cloud.gov](https://www.cloud.gov) or any Cloud Foundry install, using the [PHP Buildpack](https://docs.cloudfoundry.org/buildpacks/php/index.html).

This is an out-of-the-box implementation of Drupal 7.  It's an example of how common PHP applications can run on Cloud Foundry. Different Cloud Foundry installs will have different parameters for `create-service`, and different base domain.

### Usage

1. Clone the app (i.e. this repo).

    ```bash
    git clone https://github.com/18F/cf-ex-drupal.git cf-ex-drupal
    cd cf-ex-drupal
    ```

1.  If you don't have one already, create a MySQL service. If you have a cloud.gov account, the following command will create a free MySQL database.

    ```bash
    cf create-service aws-rds shared-mysql my-db-service
    ```

1. Edit `manifest.yml` and change `DRUPAL_HASH_SALT`. This should be unique for every installation. (Note that you do *not* need to edit the database configuration in `htdocs/sites/default/settings.php`. The file included with this example will automatically pull that information from `VCAP_SERVICES`.)

2. Push the app to Cloud Foundry

    ```bash
    cf push --random-route
    ```

1. On your first push, you'll need to access the install script. It'll be `https://<the-'urls'-value-the-command-line-just-confirmed>/install.php`. (The `urls` value will end with the base domain `.app.cloud.gov`.) Follow instructions there to complete the install.  After it's done, you'll be all set.

### How It Works

When you push the application here's what happens.

1. The local bits are pushed to your target. This is small, five files around 25k. It includes the changes we made and a buildpack extension for Drupal.
1. The server downloads the [PHP Buildpack] and runs it. This installs HTTPD and PHP.
1. The buildpack sees the extension that we pushed and runs it. The extension downloads the stock Drupal file from their server, unzips it and installs it into the `htdocs` directory. It then copies the rest of the files that we pushed and replaces the default Drupal files with them. In this case, it's just the `htdocs/sites/default/settings.php` file.
1. At this point, the buildpack is done and Cloud Foundry runs our droplet.


### Configuration needed to run Drupal in this example:

1. Include a [custom list of HTTPD modules](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.bp-config/httpd/extra/httpd-modules.conf#L15).  These are the same as the default, but I've added `mod_access_compat`, which is necessary because Drupal's `.htaccess` file still uses HTTPD 2.2 config.

1. Add [the PHP extensions](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.bp-config/options.json#L2) that are needed by Drupal.

1. Add a [custom buildpack extension](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.extensions/drupal/extension.py), which downloads Drupal on the remote server.  This is not strictly necessary, but it saves one from having to upload a lot of files on each push.  The version of Drupal that will be installed is [here](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/.extensions/drupal/extension.py#L15).

1. Include a [copy of the default settings from the standard Drupal install](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/htdocs/sites/default/settings.php).  This is [modified](https://github.com/cloudfoundry-samples/cf-ex-drupal/blob/master/htdocs/sites/default/settings.php#L216-L251) to pull the database configuration from the `VCAP_SERVICES` environment variable, which is populated with information from services that are bound to the app.  Since we bind a MySQL service to our app in the instructions above, we search for that and automatically configure it for use with Drupal.

### Caution

Please read the following before using Drupal in production on Cloud Foundry.

1. Drupal is designed to write to the local file system.  This does not work well with Cloud Foundry, as an application's [local storage on Cloud Foundry] is ephemeral.  In other words, Drupal will write things to the local disk and they will eventually disappear.  

  You can work around this in some cases, like with media, by using a storage service like Amazon S3 or CloudFront.  However there may be other cases where Drupal or Drupal plugins try to write to the disk, so test your installation carefully.

1. This is not an issue with Drupal specifically, but PHP stores session information to the local disk.  As mentioned previously, the local disk for an application on CloudFoundry is ephemeral, so it is possible for you to lose session and session data.  If you need reliable session storage, look at storing session data in an SQL database or with a NoSQL service.


[PHP Buildpack]:https://github.com/cloudfoundry/php-buildpack
[local storage on CloudFoundry]:https://docs.cloudfoundry.org/devguide/deploy-apps/prepare-to-deploy.html#filesystem
