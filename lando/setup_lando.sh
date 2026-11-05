#!/usr/bin/env bash
#
# First-time Lando setup for CASBS Gryphon. See README.md.
#
# Safe to re-run: it never overwrites local files that already exist
# (.lando.yml, drush/local.drush.yml, local.settings.php, ...).

# Three-fingered Claw Technique
yell() { echo "$0: $*" >&2; }
die() { yell "$*"; exit 111; }
try() { "$@" || die "cannot $*"; }

# Run from the repository root, wherever the script is called from.
cd "$(dirname "$0")/.." || die "cannot cd to the repository root"

LOCAL_URI="https://casbs.lndo.site"

yell "Setting up your site using lando."

# .lando.yml is tracked in git. Only fall back to the example if it is missing.
[ -f .lando.yml ] || try cp ./lando/example.lando.yml ./.lando.yml

# .lando.yml mounts ~/.acquia (Acquia CLI credentials). Create it so Docker
# does not create it as root.
try mkdir -p "$HOME/.acquia"

try lando start
try lando composer install -n

# Drush config: database credentials and (optional) Acquia API token.
[ -f ./drush/local.drush.yml ] || try cp ./lando/lando.drush.yml ./drush/local.drush.yml

# Site-level Drush URI. The tracked default.local.drush.yml template contains an
# unexpanded `${uri}` placeholder, which makes `lando drush` (without an alias)
# fail with "Invalid URI: Host is malformed", so write the real URI instead.
if [ ! -f ./docroot/sites/default/local.drush.yml ]; then
  printf "options:\n  uri: '%s'\n" "$LOCAL_URI" > ./docroot/sites/default/local.drush.yml \
    || die "cannot write docroot/sites/default/local.drush.yml"
fi

# Creates salt.txt, settings.php and the local.settings.php files (only if they
# don't exist yet) using the database credentials from drush/local.drush.yml.
# Use the alias so a re-run works even if an older local.drush.yml still has
# the `${uri}` placeholder.
try lando drush @default.local settings

# Fetch the SAML/secrets files from Acquia prod into ./keys. Needs SSH access.
# Optional: settings only load keys/secrets.settings.php if it exists.
lando drush @default.local sws:keys \
  || yell "Skipped keys (no Acquia SSH access?). SAML login won't work locally."

read -r -p "Copy the database and files from production now? [y/N] " answer
if [[ "$answer" == "y" || "$answer" == "Y" ]]; then
  # sql:sync + rsync from @default.prod. --timeout=0 because a full sync
  # takes longer than Composer's default 300 second script timeout.
  try lando composer run-script --timeout=0 sync-casbs
  try lando drush @default.local deploy -y
  yell "Your site is good to go."
else
  yell "Skipped the production sync. When you are ready, run:"
  yell "  lando composer run-script --timeout=0 sync-casbs && lando drush @default.local deploy -y"
  yell "No prod access? Install a clean site instead:"
  yell "  lando drush @default.local drupal:install -n"
fi

echo
echo "Site: ${LOCAL_URI}/"
echo "Log in with: lando drush @default.local uli --uri=${LOCAL_URI}"
