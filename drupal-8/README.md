# Working with Drupal8 in cloud.gov (and Cloud Foundry)

[Drupal](https://drupal.org) has been a popular PHP-based framework for content-management systems and web applications. It was not written as a cloud-native framework, so it takes a bit of tweaking to run on cloud.gov. If you're on a _greenfield_ project you may want to consider [Laravel](https://laravel.com) as your framework, but if you're going forward with Drupal then this guide will get you there.

This guide is written for [cloud.gov](https://cloud.gov/) users, but will work for any Cloud Foundry site. Just replace the specifics for `aws-rds` and `s3` with your site equivalents and everything should just work.

## Quickstart

Assuming you've already clone this repo, and are using this directory:

Update manifest.yml with:

1. the correct value for `AWS_S3_ENDPOINT` (or comment out for U.S. commercial S3 endpoint)
1. the correct admin name, `ACCOUNT_NAME`
1. run the the following commands:


```
cf create-service s3 basic-public d8ex-s3
cf create-service aws-rds shared-mysql d8ex-db
cf push d8ex --no-start -b  https://github.com/cloudfoundry/apt-buildpack.git
# Set the ACCOUNT_PASS as an environment variable, or it'll be auto-generated
# and recorded in the logs
cf set-env d8ex ACCOUNT_PASS "your-account-pass"
cf v3-push d8ex -b https://github.com/cloudfoundry/apt-buildpack.git -b php_buildpack
```

Separately, 
```
cf logs d8ex
```

When the `v3-push` command completes:
- Visit the site URL
- Login with `ACCOUNT_NAME` and `ACCOUNT_PASS`
- Update 

## Gotchas:

1. `'v3-push' is not a registered command. See 'cf help'` : You'll need to update your CF CLI install.


----

# Notes below this point are still in progress

----
# Building your own Drupal project


Install composer:

```
brew install homebrew/php/composer
```

Create a fresh Drupal8 project named `d8ex`
```
composer create-project drupal-composer/drupal-project:8.x-dev d8ex --stability dev --no-interaction
composer require drupal/flysystem_s3
```

## Trying on cloud.gov

Added manifest.yml with built-in service reference to d8ex-db and d8ex-s3

Updated settings.php to pull DB from ENV 

`cf push` initially fails because:

```
Could not scan for classes inside "scripts/composer/ScriptHandler.php" which does not appear to be a file nor a folder
Class DrupalProject\composer\ScriptHandler is not autoloadable, can not call pre-install-cmd script
```

Which I stepped over with by eliding it from composer.json:

```
    "autoload": {
        "classmap": [
            "scripts/composer/ScriptHandler.php" // deleted this line
        ]
```


# SSH Setup

If you `cf ssh` to the application, you'll need to set the following variables to work effectively with the DB or PHP:

```
export HOME=/home/vcap/app
export DEPS_DIR=/home/vcap/deps
export PYTHONPATH=/home/vcap/app/.bp/lib
export TMPDIR=/home/vcap/tmp
export LIBRARY_PATH=/home/vcap/deps/0/lib
export PHPRC=/home/vcap/app/php/etc
export LD_LIBRARY_PATH=/home/vcap/deps/0/lib:/home/vcap/app/php/lib
export PATH=/home/vcap/deps/0/bin:/usr/local/bin:/usr/bin:/bin:/home/vcap/app/php/bin:/home/vcap/app/php/sbin
```

# Development notes

Use a dedicate mysql DB so it's easier to clean up without having to reprovision:

# References

These are complementary to what's described here for running on Heroku

https://www.fomfus.com/articles/how-to-create-a-drupal-8-project-for-heroku-part-1
https://www.fomfus.com/articles/how-to-deploy-a-drupal-8-project-to-heroku-part-2


# Known issues

- [ ] Flysystem s3 needs testing
- [ ] HASH SALT not set yet
- [ ] Install with standard profile instead of minimal
- [ ] Needs testing in terms of fresh install from composer
- [ ] Determine if `apt` buildpack is still necessary with `drupal-console`, as it may use PHP libraries instead of the mysql command line.