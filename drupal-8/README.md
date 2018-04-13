# Working with Drupal8 in cloud.gov (and Cloud Foundry)

[Drupal](https://drupal.org) has been a popular PHP-based framework for content-management systems and web applications. It was not written as a cloud-native framework, so it takes a bit of tweaking to run on cloud.gov, or any other Cloud Foundry implementation. If you're on a _greenfield_ project you may want to consider other PHP frameworks, but if you're going forward with Drupal then this guide will get you there.

This guide is written for [cloud.gov](https://cloud.gov/) users, but will work for any Cloud Foundry site. Just replace the specifics for `aws-rds` (mysql) and `s3` (s3 bucket) with your site equivalents and everything should just work. 

## Quickstart

### Preliminaries

You can demonstrate a fully-functional Drupal install assuming:
1. you've already cloned this repo, and
1. are using this directory

If you're _not_ using cloud.gov:
* Update `manifest.yml` to change the `AWS_S3_ENDPOINT` 

### Demo

Log in to Cloud Foundry (e.g. `cf login --sso -a https://api.fr.cloud.gov`), then run the following commands for cloud.gov:

First, set an admin password (if you're using Unix-type shell):
```
export ADMIN_PASS=yourpassword
```
Then create a `user-provided-service` to provide that password to the application:
```
cf create-user-provided-service drupal8-example-secrets \
  -p '{"ADMIN_NAME":"cloudgov-admin", "ADMIN_PASS":"'${ADMIN_PASS:=secret}'"}'
```

> If you are using PowerShell, then replace `secret` below with a real password
   ``` 
   cf create-user-provided-service drupal8-example-secrets \
  -p '{"ADMIN_NAME":"cloudgov-admin", "ADMIN_PASS":"secret"}'

Run these commands to create the backing services:
```
cf create-service s3 basic-public drupal8-example-s3
cf create-service aws-rds shared-mysql drupal8-example-db
```

Now, use the `push` command to send the files to Cloud Foundry, package, and start the application:
```
cf push 
```

This project uses the "multi-buildpack" feature of Cloud Foundry, since we need `apt` to install the `mysql` client, and the `php_buildpack` for Drupal itself. 

When the `push` command completes:
- Visit the site URL
- Login with `ADMIN_NAME` and `ADMIN_PASS`
  - If you didn't run the `export ADMIN_PASS` command, then your password is `secret`
- Set up use of S3 Flysystem instead of local disk:
  - As a admin, go to Configuration -> File system, and set "Default download method" `Flysystem: s3`
- On a default Drupal install, there should be two fields using the local filesystem. Those fields need to be updated to use Amazon S3:
  - _Image_ field on _Article_ content type (Structure > Content types > Article > Manage fields > Image)
  - _Image_ field for _User_ profile picture (Configuration > People > Account settings > Manage fields > Picture)

You are all set to use Drupal with Cloud Foundry\*. Congratulations!

## Gotchas:

1. `'v3-push' is not a registered command. See 'cf help'` : You'll need to update your CF CLI install.

# Building your own Drupal project

This project demonstrates a Drupal project initiated with `composer`. Let's step through the process so you can repeat for your Drupal project. The steps are for MacOS. 

Install composer:
```
brew install homebrew/php/composer
```

Create a fresh Drupal8 project named `drupal8-example`:
```
composer create-project drupal-composer/drupal-project:8.x-dev drupal8-example --stability dev --no-interaction
cd drupal8-example
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
* `web/sites/default/settings.cf.php`

E.g., if you've cloned this project to $HOME/Projects/18f/cf-ex-drupal/, then:

```
SOURCE_DRUPAL=$HOME/Projects/18f/cf-ex-drupal/drupal-8/
cp -r $SOURCE_DRUPAL/.bp-config .
git add .bp-config
for f in .cfignore .gitignore apt.yml bootstrap.sh manifest.yml web/sites/default/settings.cf.php; do
  cp $SOURCE_DRUPAL/$f $f
  git add $f
done
```

Add the service parsing to `settings.php` by pasting in the following:
```
if (file_exists($app_root . '/' . $site_path . '/settings.cf.php')) {
  include $app_root . '/' . $site_path . '/settings.cf.php';
}
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

# References

These blog posts by Federico Jaramillo Martínez on running Drupal 8 on Heroku are complementary to this guide, and provided this author with some useful tips

https://www.fomfus.com/articles/how-to-create-a-drupal-8-project-for-heroku-part-1<br>
https://www.fomfus.com/articles/how-to-deploy-a-drupal-8-project-to-heroku-part-2

# Licenses

Core Drupal is under the [GNU General Public License v2](./LICENSE). 

This project to enable running Drupal in cloud.gov is a work of the United
States Government, and is in the public domain within the United
States.  Additionally, we waive copyright and related rights to 
this work worldwide through the [CC0 1.0 Universal public
domain dedication](https://creativecommons.org/publicdomain/zero/1.0/).
