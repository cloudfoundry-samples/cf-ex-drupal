#!/bin/bash 
set -euxo pipefail

: ${ACCOUNT_NAME?} ${ACCOUNT_PASS?}

fail() {
    echo FAIL $@
    exit 1
}

bootstrap() {
    creds=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][0].credentials')
    [ $creds = "null" ] && fail "creds are null; need to bind database?"

    db_type=mysql
    db_user=$(echo $creds | jq -r '.username')
    db_pass=$(echo $creds | jq -r '.password')
    db_port=$(echo $creds | jq -r '.port')
    db_host=$(echo $creds | jq -r '.host')
    db_name=$(echo $creds | jq -r '.db_name')

    drupal site:install standard --root=$HOME/web --no-interaction \
        --account-name=${ACCOUNT_NAME:-$(gen_cred ACCOUNT_NAME)} \
        --account-pass=${ACCOUNT_PASS:-$(gen_cred ACCOUNT_PASS)} \
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

