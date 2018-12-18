#!/bin/bash 
set -euo pipefail	
# set -x # use for debugging purposes only

SECRETS=$(echo $VCAP_SERVICES | jq -r '.["user-provided"][] | select(.name == "drupal8-example-secrets") | .credentials')
APP_NAME=$(echo $VCAP_APPLICATION | jq -r '.name')
APP_ROOT=$(dirname "${BASH_SOURCE[0]}")

fail() {
    echo FAIL $@
    exit 1
}

bootstrap() {
    ADMIN_NAME=$(echo $SECRETS | jq -r '.ADMIN_NAME')
    ADMIN_PASS=$(echo $SECRETS | jq -r '.ADMIN_PASS')

    : ${ADMIN_NAME?} ${ADMIN_PASS?}

    rds_creds=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][0].credentials')
    [ $rds_creds = "null" ] && fail "rds_creds are null; need to bind database?"

    db_type=mysql
    db_user=$(echo $rds_creds | jq -r '.username')
    db_pass=$(echo $rds_creds | jq -r '.password')
    db_port=$(echo $rds_creds | jq -r '.port')
    db_host=$(echo $rds_creds | jq -r '.host')
    db_name=$(echo $rds_creds | jq -r '.db_name')

    drupal site:install standard --root=$HOME/web --no-interaction \
        --account-name=${ADMIN_NAME:-$(gen_cred ADMIN_NAME)} \
        --account-pass=${ADMIN_PASS:-$(gen_cred ADMIN_PASS)} \
        --langcode="en" \
        --db-type=$db_type \
        --db-user=$db_user \
        --db-pass=$db_pass \
        --db-port=$db_port \
        --db-host=$db_host \
        --db-name=$db_name 
}

drush --root=$HOME/web core-status bootstrap | grep -q "Successful" || bootstrap

drush --root=$HOME/web pm-info flysystem_s3 --fields=Status | grep -q enabled ||
    drush --root=$HOME/web pm-enable --yes flysystem_s3

