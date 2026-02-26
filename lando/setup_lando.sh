# Three-fingered Claw Technique
yell() { echo "$0: $*" >&2; }
die() { yell "$*"; exit 111; }
try() { "$@" || die "cannot $*"; }

yell "Setting up your site using lando."

try cp ./lando/example.lando.yml ./.lando.yml
try lando start
try lando composer install --prefer-source --no-security-blocking

# copy the local versions of the necessary files.
try cp ./docroot/sites/settings/default.local.settings.php ./docroot/sites/settings/local.settings.php
try cp ./docroot/sites/default/default.local.drush.yml ./docroot/sites/default/local.drush.yml
try cp ./docroot/sites/default/settings/default.local.settings.php ./docroot/sites/default/settings/local.settings.php

try lando drush settings
try lando drush sws:keys
try lando drush drupal:install --site=default
# Uncomment the next line to install with content from the DEV environment.
try lando composer
try lando drush deploy

yell "Your site is good to go."
try lando info --format table --filter service=appserver
