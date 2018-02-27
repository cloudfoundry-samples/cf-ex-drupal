# cloud.gov (and Cloud Foundry) Drupal examples

This repository includes two examples of using Drupal with [Cloud Foundry](https://cloudfoundry.org), the system that's used by the (cloud.gov)[https://cloud.gov] platform-as-a-service offering.

* [Drupal 8](./drupal-8/README.md): This example installs PHP and Drupal, then configures the site as a standard installation, setting the administrator username and password to values specified as Cloud Foundry environment variables. It uses MySQL for the database, and AWS S3 for asset storage.
* [Drupal 7](./drupal-7/README.md): This example installs PHP and Drupal, then you complete the installation, with username and password, by visiting the unauthenticated `/install.php` page in the app. It uses MySQL for the database, but doesn't handle asset storage.

The examples are separate works and are covered by the license agreements
in each subdirectory. 

The Drupal 8 code is under the [GNU General
Public License v2](./drupal-8/LICENSE), while this project to
enable running Drupal 8 in Cloud Foundry is in the public domain
through the [CC0 1.0 Universal public domain
dedication](https://creativecommons.org/publicdomain/zero/1.0/).

The Drupal 7 example is under the [Apache License
2.0](http://www.apache.org/licenses/LICENSE-2.0) (pending acceptance of
https://github.com/cloudfoundry-samples/cf-ex-drupal/pull/6).
