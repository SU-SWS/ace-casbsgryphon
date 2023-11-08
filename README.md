# ACE CASBS Gryphon

This stack is based on the ace-gryphon stack, but it is customized for CASBS.  The 11.x branch is the default branch, and contains the Drupal 10 version of the site.

---
# Setup Local Environment - Lando

The easiest way to set up a local development environment is going to be using Lando and Docker in a linux-like environment.  If you are setting this up on a fresh machine, with none of the prerequisites present, it may take you a little time for the initial setup, but once the prerequisites are in place, you should be able to set up the CASBS site locally in less than 20 minutes.

## Prerequisites

### Linux/MacOS

1. Set up Docker on your distro of choice.  Instructions for installing Docker in linux [can be found here](https://docs.docker.com/desktop/linux/install/), and these are [the MacOS instructions.](https://docs.docker.com/desktop/mac/install/)
2. Set up Lando on your distro of choice.  Instructions for installing Lando in linux [can be found here](https://docs.lando.dev/getting-started/installation.html#linux), and these are [the MacOS instructions.](https://docs.lando.dev/getting-started/installation.html#macos)

### Windows/WSL

Because Docker works best with Windows Subsystem for Linux V.2, we suggest you proceed that way.
1. [Install Windows Subsystem for Linux V.2](https://docs.microsoft.com/en-us/windows/wsl/install)
2. [Install Docker Desktop for Windows, and enable the WSL2 extensions.](https://docs.docker.com/desktop/windows/wsl/)
3. [Install Docker for linux in your WSL2 environment.](https://docs.docker.com/desktop/linux/install/ubuntu/)
4. Install Lando for linux in your WSL2 environment.  [Instructions can be found here.](https://docs.lando.dev/getting-started/installation.html#linux)

No other prerequisites are necessary except Git, though you may find it helpful to have PHP 8.1+, and Composer 2 installed locally on your system.  Once you have met the prerequisites, you can proceed with the installation.

## Installation:

1. Clone this repository somewhere on your local system.
2. Change directory into the location where you cloned the repository.
3. Run `./lando/setup_lando.sh`
4. Run `lando composer sync-casbs`.  This will sync the database and files to your local system.  The sync comes from the Dev server.
5. Run `lando drush cr` to clear the caches for the updated database.  If you don't do this step, you may encounter an error when you try to load the site in a browser.
6. Your application should be available locally at http://casbs.lndo.site/

If using lando, prefix any `blt` commands with `lando`, e.g., `lando blt drupal:install`.  The same applies to composer commands.

### Lando local drush aliases

The CASBS site is `@default` -- e.g, `@default.local, @default.dev, @default.test`

So, for instance, to clear the caches on your local copy of CASBS:
```
lando drush -y @default.local cr
```

----
# Setup Local Environment - Native LAMP Stack

TL;DR
```
composer update --prefer-source --no-interaction
cp lando/example.local.blt.yml blt/local.blt.yml
```
Edit the `blt/local.blt.yml` file to match your setup (e.g., database settings, hostname, etc.).
```
cp simplesamlphp/config/default.local.config.php simplesamlphp/config/local.config.php
blt blt:init:settings
blt source:build:simplesamlphp-config
```

BLT provides an automation layer for testing, building, and launching Drupal 8 applications. For ease when updating codebase it is recommended to use  Drupal VM. If you prefer, you can use another tool such as Docker, [DDEV](https://docs.acquia.com/blt/install/alt-env/ddev/), [Docksal](https://docs.acquia.com/blt/install/alt-env/docksal/), [Lando](https://docs.acquia.com/blt/install/alt-env/lando/), (other) Vagrant, or your own custom LAMP stack, however support is very limited for these solutions.
1. Install Composer dependencies.
After you have forked, cloned the project and setup your blt.yml file install Composer Dependencies. (Warning: this can take some time based on internet speeds.)
    ```
    $ composer install
    ```
2. Setup a local blt alias.
If the blt alias is not available use this command outside and inside vagrant (one time only).
    ```
    $ composer run-script blt-alias
    ```
3. Set up local BLT
Copy the file `blt/example.local.blt.yml` and name it `local.blt.yml`. Populate all available information with your local configuration values.

4. Setup Local settings
After you have the `local.blt.yml` file configured, set up the settings.php for you setup.
    ```
    $ blt blt:init:settings
    ```
5. Get secret keys and settings
SAML and other certificate files will be download for local use.
     ```
    $ blt sws:keys
    ```

Optional:
If you wish to not provide statistics and user information back to Acquia run
     ```
    $ blt blt:telemetry:disable --no-interaction
    ```
---
## Other Local Setup Steps

1. Set up frontend build and theme.
By default BLT sets up a site with the lightning profile and a cog base theme. You can choose your own profile before setup in the blt.yml file. If you do choose to use cog, see [Cog's documentation](https://github.com/acquia-pso/cog/blob/8.x-1.x/STARTERKIT/README.md#create-cog-sub-theme) for installation.
See [BLT's Frontend docs](https://docs.acquia.com/blt/developer/frontend/) to see how to automate the theme requirements and frontend tests.
After the initial theme setup you can configure `blt/blt.yml` to install and configure your frontend dependencies with `blt setup`.

2. Pull Files locally.
Use BLT to pull all files down from your Cloud environment.

   ```
   $ blt drupal:sync:files
   ```

3. Sync the Cloud Database.
If you have an existing database you can use BLT to pull down the database from your Cloud environment.
   ```
   $ blt sync
   ```

----
# Config Management
~~Each site has the ability to determine its own configuration management strategy.
The default site in this repo will be using a configuration management that uses
the configuration from the `stanford_profile`. By default this is the behavior
of all other sites unless defined within their own settings.php.

There are three options a site can choose from:
1. Do nothing and the configuration sync directory will use what is in `stanford_profile`.
2. Modify the configuration sync directory to a desired directory such as another profile.
3. Modify the configuration sync directory to point to an empty directory. This
will bypass any configuration management strategy and the site's configuration will be updated via update hooks.~~

---
# Resources

Additional [BLT documentation](https://docs.acquia.com/blt/) may be useful. You may also access a list of BLT commands by running this:
```
$ blt
```

Note the following properties of this project:
* Primary development branch: 1.x
* Local environment: @default.local
* Local site URL: http://local.example.loc/

## Working With a BLT Project

BLT projects are designed to instill software development best practices (including git workflows).

Our BLT Developer documentation includes an [example workflow](https://docs.acquia.com/blt/developer/dev-workflow/).

### Important Configuration Files

BLT uses a number of configuration (`.yml` or `.json`) files to define and customize behaviors. Some examples of these are:

* `blt/blt.yml` (formerly blt/project.yml prior to BLT 9.x)
* `blt/local.blt.yml` (local only specific blt configuration)
* `box/config.yml` (if using Drupal VM)
* `drush/sites` (contains Drush aliases for this project)
* `composer.json` (includes required components, including Drupal Modules, for this project)

## Helpful links and resources

* SWS devguide: https://sws-devguide.stanford.edu/
* Decanter styleguide: https://decanter.stanford.edu/
* Stanford Identity guide: https://identity.stanford.edu/

