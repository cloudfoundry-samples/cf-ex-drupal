

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





