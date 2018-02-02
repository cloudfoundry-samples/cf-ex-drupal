

# local notes

```
brew install homebrew/php/composer
```
created with
```
composer create-project drupal-composer/drupal-project:8.x-dev cg-drupal8-example --stability dev --no-interaction
```

Trying lando locally  from github.com/lando

```
lando init # Select Drupal8 and webroot should be `./webroot`

git add .lando.yml

lando start
```

Setup DB using info from...:

```
lando info
```

Note that the DB is hostname `database`, and now if you go to the site at
http://cg-drupal-8-example.lndo.site you can configure it. The settings, at
this point though, are all saved in `drupal-8/web/sites/default/settings.php`

## Trying on cloud.gov

```
cf create-service aws-rds medium-psql drupal8-example-db
```

Added manifest.yml with built-in service reference to drupal8-example-db and drupal8-example-s3

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

## multi buildpacks

https://docs.cloudfoundry.org/buildpacks/use-multiple-buildpacks.html

Push the application with the binary buildpack with the --no-start flag:

```
cf push drupal8-example --no-start -b  https://github.com/cloudfoundry/apt-buildpack.git
```

This command pushes the application but does not start it.
Upgrade the application to multiple buildpacks, and specify the buildpacks:

```
cf v3-push drupal8-example -b https://github.com/cloudfoundry/apt-buildpack.git -b php_buildpack
```

SSH

```
cf ssh drupal8-example 

##

export DEPS_DIR=/home/vcap/deps
export PYTHONPATH=/home/vcap/app/.bp/lib
export TMPDIR=/home/vcap/tmp
export LIBRARY_PATH=/home/vcap/deps/0/lib
export PHPRC=/home/vcap/app/php/etc
export LD_LIBRARY_PATH=/home/vcap/deps/0/lib:/home/vcap/app/php/lib
export CF_SYSTEM_CERT_PATH=/etc/cf-system-certificates
export PATH=/home/vcap/deps/0/bin:/usr/local/bin:/usr/bin:/bin:/home/vcap/app/php/bin:/home/vcap/app/php/sbin



creds=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][0].credentials')
db_type=postgres
db_user=$(echo $creds | jq -r '.username')
db_pass=$(echo $creds | jq -r '.password')
db_port=$(echo $creds | jq -r '.port')
db_host=$(echo $creds | jq -r '.host')
db_name=$(echo $creds | jq -r '.db_name')

cd app/web


drupal site:install minimal --no-interaction \
  --langcode="en" \
  --account-name=joe --account-pass=mom \
  --db-type=$db_type \
  --db-user=$db_user \
  --db-pass=$db_pass \
  --db-port=$db_port \
  --db-host=$db_host \
  --db-name=$db_name 
```

