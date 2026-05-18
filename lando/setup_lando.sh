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
try cp ./lando/lando.drush.yml ./drush/local.drush.yml

echo "Please edit ./drush/local.drush.yml file and make sure"
echo "the values are correct for your database, app-key, and"
echo "app-secret are correct. You can get the app values from "
echo "Acquia at https://profile.acquia.com/tokens"

while true; do
    read -r -p "Type 'y' to continue: " answer
    [[ "$answer" == "y" ]] && break
done
echo "Continuing..."

try lando drush settings
try lando drush sws:keys
try lando drush -y sql:sync @default.prod @default.local --structure-tables-list=search_*,cache_*,history,watchdog,sessions
try lando drush -y rsync @default.prod:%files @default.local:%files
try lando drush deploy

yell "Your site is good to go."
try lando info --format table --filter service=appserver
