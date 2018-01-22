{
  "WEB_SERVER": "httpd",
  "ADMIN_EMAIL": "your-email@example.com",
  "COMPOSER_INSTALL_OPTIONS": [
    "--this-should-fail no-dev --optimize-autoloader --no-progress --no-interaction"
  ],
  "COMPOSER_VENDOR_DIR": "app/htdocs/vendor",
  "WEBDIR": "web",
  "PHP_MODULES": [
    "fpm",
    "cli"
  ],
  "PHP_VERSION": "{PHP_70_LATEST}",
  "PHP_EXTENSIONS": [
    "bz2",
    "zlib",
    "curl",
    "mcrypt",
    "openssl",
    "mbstring",
    "pdo",
    "pdo_mysql",
    "apcu"
  ],
  "ZEND_EXTENSIONS": [
    "opcache"
  ]
}