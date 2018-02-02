#!/bin/bash -exv

cd web

gen_cred() {
    cred=$(cat /dev/urandom | head | gtr -dc _A-Z-a-z-0-9 | head -c32; echo;)
    echo $1 $cred >&2
    echo $cred
}

bootstrap() {
    creds=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][0].credentials')
    db_type=mysql
    db_user=$(echo $creds | jq -r '.username')
    db_pass=$(echo $creds | jq -r '.password')
    db_port=$(echo $creds | jq -r '.port')
    db_host=$(echo $creds | jq -r '.host')
    db_name=$(echo $creds | jq -r '.db_name')

    drupal site:install standard --no-interaction \
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

[ drush core-status bootstrap | grep -q "Successful" ] || bootstrap



