# ACE CASBS Gryphon

The CASBS website. Based on the SWS ace-gryphon stack, customized for CASBS, and
hosted on Acquia Cloud.

- Default and development branch: `12.x`
- Drupal 11, PHP 8.4, Drush 13, MySQL 8.0
- Install profile: [stanford_profile](https://github.com/SU-SWS/stanford_profile),
  kept as a git subtree in `docroot/profiles/custom/stanford_profile`
- Local site: https://casbs.lndo.site/ (Lando)
- Drush aliases: `@default.local`, `@default.dev`, `@default.test`, `@default.prod`

---

# Local development with Lando

## Prerequisites

1. **Git.**
2. **Docker.**
   - macOS: [Docker Desktop](https://docs.docker.com/desktop/setup/install/mac-install/).
   - Linux: [Docker Engine](https://docs.docker.com/engine/install/).
   - Windows: [install WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install)
     (Ubuntu), then Docker Desktop with the
     [WSL 2 backend](https://docs.docker.com/desktop/features/wsl/). Do all of
     the steps below inside the WSL 2 shell, and clone the repository into the
     Linux file system (for example `~/code`), not under `/mnt/c`. Builds are
     much slower on `/mnt/c`.
3. **Lando 3.26 or later**: [Linux / WSL 2](https://docs.lando.dev/install/linux.html),
   [macOS](https://docs.lando.dev/install/macos.html),
   [Windows](https://docs.lando.dev/install/windows.html).
4. **Acquia access** to the CASBS application (ask the CASBS web team), with
   your SSH public key added to your Acquia Cloud profile. The setup script
   copies the database, files and secrets from production over SSH.
   - Lando loads the keys in your `~/.ssh` into the containers at
     `lando start`. If you add a key later, run `lando restart`.
   - Passphrase-protected keys must be loaded in `ssh-agent` (`ssh-add`).
   - To check your access once Lando is running:
     `lando drush @default.prod status` (read-only). It should print the prod
     site's status, not `Permission denied (publickey)`.

   No Acquia access? See [No prod access?](#no-prod-access).

You don't need PHP or Composer on your machine. They run inside Lando
(`lando php`, `lando composer`). You only need Node on your machine for theme
work (see [Theme / CSS changes](#theme--css-changes)).

Optional: an [Acquia API token](https://cloud.acquia.com/a/profile/tokens). You
only need it for the `drush sws:acquia:*` commands. Put the key and secret in
`app-key` / `app-secret` in `drush/local.drush.yml`. Never commit that file.

## First-time setup

```bash
git clone git@github.com:SU-SWS/ace-casbsgryphon.git
cd ace-casbsgryphon
./lando/setup_lando.sh
```

The script is safe to re-run. It doesn't overwrite local files that already
exist. It:

1. Starts Lando and runs `composer install`. The first start builds the
   containers and takes several minutes.
2. Creates the gitignored local files: `drush/local.drush.yml` (from
   `lando/lando.drush.yml`, with the Lando database credentials),
   `docroot/sites/default/local.drush.yml` (sets the local URI), and then
   `lando drush @default.local settings` creates `salt.txt` and the
   `local.settings.php` files.
3. Runs `lando drush @default.local sws:keys`, which copies the SAML/secrets
   files from Acquia prod into `keys/`. If that fails (no SSH access), the
   script warns and carries on. `keys/` is optional; without it SAML login
   doesn't work locally.
4. Asks whether to copy the production database and files. Answer `y` on a new
   machine. It then runs `sync-casbs` and `drush deploy`.

Then open https://casbs.lndo.site/ and log in with:

```bash
lando drush @default.local uli --uri=https://casbs.lndo.site
```

Lando serves the site with its own CA certificate. If your browser shows a
certificate warning (always the case for Windows browsers with WSL 2), trust
the Lando CA: see [Lando: trusting the CA](https://docs.lando.dev/core/v3/security.html#trusting-the-ca).

### No prod access?

Answer `N` at the "Copy the database and files from production now?" prompt,
then install a clean stanford_profile site (the same thing CI does):

```bash
lando drush @default.local drupal:install -n
```

This gives you a fresh stanford_profile site without CASBS production content.
It replaces whatever is in your local database.

## Daily commands

Prefix `drush` and `composer` with `lando`. Use the `@default.local` alias.

```bash
lando start
lando stop
lando restart
lando info                                   # URLs and services

lando drush @default.local cr
lando drush @default.local status
lando drush @default.local config:status
lando drush @default.local cex -y            # export config after changing it in the UI
lando drush @default.local deploy -y         # updb + cim + cr + deploy hooks, e.g. after pulling new code

lando composer install
lando composer update vendor/package --with-dependencies   # targeted updates only

lando db-export snapshot.sql.gz              # local DB backup
lando db-import snapshot.sql.gz
```

### Refresh from production

These commands **overwrite your local database** (and files) with production:

```bash
lando composer run-script --timeout=0 sync-casbs   # database + public files; cache_*, search_*, history, watchdog, sessions are structure-only
lando composer run-script --timeout=0 sync-db      # full database only
lando drush @default.local deploy -y
```

Use `run-script --timeout=0`. A plain `lando composer sync-casbs` stops after
Composer's 300 second script timeout.

`@default.dev`, `@default.test` and `@default.prod` are the live Acquia sites.
Only run read-only commands against them.

### Running tests

PHPUnit (unit and kernel tests, the same as CI). First generate
`docroot/core/phpunit.xml` (gitignored). `phpunit:config` is an alias of
`sws:source:tests:phpunit:config`, and its database defaults already match
Lando (`drupal`/`drupal`@`database`):

```bash
lando drush @default.local phpunit:config
lando ssh -c "mkdir -p /app/docroot/sites/simpletest"
```

Then run single test files:

```bash
lando phpunit /app/docroot/profiles/custom/stanford_profile/tests/src/Unit/Hook/InstallHooksTest.php
lando ssh -c "env SIMPLETEST_DB=mysql://drupal:drupal@database/drupal /app/vendor/bin/phpunit -c /app/docroot/core /app/docroot/profiles/custom/stanford_profile/tests/src/Kernel/StanfordProfilePermissionsTest.php"
```

The `SIMPLETEST_DB` override is needed for kernel tests. The `drupal10` Lando
recipe sets `SIMPLETEST_DB` to its default `drupal10` credentials, but
`.lando.yml` sets the database credentials to `drupal`. Without the override,
kernel tests fail with `Access denied for user 'drupal10'`. Unit tests don't
need it.

The whole suite, as CI runs it, takes more than 10 minutes:

```bash
lando ssh -c "env SIMPLETEST_DB=mysql://drupal:drupal@database/drupal /app/vendor/bin/drush @default.local phpunit"
```

Codeception (the profile's suites, the same as CI):

```bash
lando drush @default.local codeception --site-domain=localhost --suite=acceptance \
  --test-dir=docroot/profiles/custom/stanford_profile/tests/codeception \
  --filter='HomePageCest:testHomepage$'
```

- `--suite` is `acceptance` or `functional`. Leave out `--filter` to run the
  whole suite.
- The HTML report is written to `artifacts/report.html`.
- CI runs these tests against a freshly installed site
  (`drush drupal:install`). Some of them fail against a production copy, and
  they create and delete content in your local database.
- The `functional` suite uses WebDriver. Locally that is the `chromedriver`
  Lando service, but `tests/codeception.dist.yml` has no local WebDriver host,
  so it only runs in CI as-is.

There is no working local `phpcs`. `drupal/coder` isn't installed, and the
`lando phpcs` and `lando codeception` tooling entries in `.lando.yml` point to
binaries that don't exist. Use the drush commands above.

## Theme / CSS changes

CASBS styles live in
`docroot/profiles/custom/stanford_profile/themes/stanford_basic/src/scss/theme/customizations/_casbs.scss`.
Lando has no Node service, so build the theme on your machine with Node 20
(see the theme's `.nvmrc`) and Yarn 4 (enable it with `corepack enable`):

```bash
cd docroot/profiles/custom/stanford_profile/themes/stanford_basic
nvm use            # or any Node 20
yarn install
yarn build         # or `yarn watch` while you work
```

Then run `lando drush @default.local cr`.

Ignore `.gitpod.yml`, `lando/example.local.blt.yml` and `lando/codeception.yml`.
They are leftovers from the old BLT setup and aren't used.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Lando commands hit the wrong site or code | Another checkout also uses the Lando name `casbs`. Run `docker ps --format '{{.Names}} {{.Label "io.lando.src"}}'` to see which `.lando.yml` the `casbs_*` containers come from. Run `lando stop` in the other checkout, then `lando start` here. |
| New PHP class (Drush command, hook class) isn't found | Run `lando restart`. `drush cr` doesn't clear the PHP opcache. |
| `lando drush ...` without an alias fails with `Invalid URI: Host is malformed` | `docroot/sites/default/local.drush.yml` still has the `${uri}` placeholder from the template. Set `uri: 'https://casbs.lndo.site'` under `options:`, or use `@default.local`. |
| `drush uli` links point to `http://default/...` | Add `--uri=https://casbs.lndo.site`. |
| `Permission denied (publickey)` during `sws:keys` or `sync-casbs` | Add your SSH public key to your Acquia Cloud profile, and check that you have access to the CASBS application. Run `lando restart` after adding a key, and `ssh-add` passphrase-protected keys first. To see which keys Lando loaded, run `lando logs -s appserver`. |
| `sync-casbs` stops after 300 seconds | Use `lando composer run-script --timeout=0 sync-casbs`. |
| Kernel tests fail with `Access denied for user 'drupal10'` | Pass `SIMPLETEST_DB=mysql://drupal:drupal@database/drupal` (see [Running tests](#running-tests)). |
| `drush cr` fatals after pulling a big update | Don't just `cr`; run `lando drush @default.local deploy -y`. |

## More documentation

- [Upstream (stanford_profile) updates](docs/upstream_updates.md)
- [CHANGELOG](CHANGELOG.md)
- SWS developer guide: https://sws-devguide.stanford.edu/
- Decanter style guide: https://decanter.stanford.edu/
- Stanford identity guide: https://identity.stanford.edu/
