# CardinalSites Changelog

4.0.5 - 2023-09-28
--------------------------------------------------------------------
- Update stanford_samlauth

4.0.4 - 2023-09-28
--------------------------------------------------------------------
- Update dependencies

4.0.3 - 2023-09-15
--------------------------------------------------------------------
- D8CORE-6842 Samlauth module upgrade

4.0.2 - 2023-07-26
--------------------------------------------------------------------
- ignore google tag settings

4.0.1 - 2023-07-13
--------------------------------------------------------------------
- Updated dependencies to add edge case in update hook.

4.0.0 - 2023-07-10
--------------------------------------------------------------------
- Added blt hook to log users out at beginning of deployment
- Updated ui patterns patch
- Updated factory hooks
- updated db update factory hook
- Updated fast404 settings
- updated dependencies
- added easy way to re-stage dev sites
- Updated stanford profile and all dependencies

3.2.1 - 2023-06-23
--------------------------------------------------------------------
- Updated su-sws/* packages as hotfix.

3.2.0 - 2023-06-22
--------------------------------------------------------------------
- Updated dependencies
- Adjusted fast 404 settings for generation

3.1.7 - 2023-03-16
--------------------------------------------------------------------
- Updated dependencies

3.1.6 - 2022-12-13
--------------------------------------------------------------------
- Updated dependencies

3.1.2 - 2022-11-30
--------------------------------------------------------------------
- Updated dependencies.

3.1.0 - 2022-10-26
--------------------------------------------------------------------
- D8CORE-6353: Set Crawl-delay to 30 seconds in robots.txt (#280)
- Fast 404 settings (#273)
- Update lando to php 8.1 (#272)
- Update Ui Patterns Patch (#271)
- Updated blt to ^13.5 and drush to ^11 (#269)
- Update config split files to match new config split module

3.0.2 - 2022-10-12
--------------------------------------------------------------------
- Block malicious 188.166.70.114 ip address.

3.0.1 - 2022-10-10
--------------------------------------------------------------------
- Block malicious IP in htaccess.

3.0.0 - 2022-08-22
--------------------------------------------------------------------
- D8CORE-6220: patched htaccess to block `CQ-API-Spyder` (#89)
- Fixed post-site-duplication factory hook by using the correct variables
- Updated dependencies and scaffold

2.5.3 - 2022-07-24
--------------------------------------------------------------------
- Downgrade react_paragraphs to functional version.
- Patch field_encrypt module to work with diseval.

2.5.2 - 2022-07-20
--------------------------------------------------------------------
- Updated all dependencies.

2.5.0 - 2022-05-18
--------------------------------------------------------------------
- Updated all dependencies
- Added post-site-duplication hook to clear config pages values (#255)
- updated acquia hooks
- Removed special treatment for link_title_formatter module
- Removed special treatment for domain_301_redirect module

2.4.0 - 2022-03-31
--------------------------------------------------------------------
- Locked menu_block module to a commit hash
- Updated all dependencies
- Configure composer to allow plugins
- Updated acquia hooks
- Added executable permission to factory hooks


2.3.2 - 2022-02-09
--------------------------------------------------------------------
- Updated all dependencies

2.3.1 - 2022-01-31
--------------------------------------------------------------------
- Updated events and profile for localist launch.

2.3.0 - 2021-12-09
--------------------------------------------------------------------
- Updated all dependencies

2.2.0 - 2021-10-21
--------------------------------------------------------------------
- Updated stanford_profile_helper to fix bulk editing issue.

2.2.0 - 2021-10-20
--------------------------------------------------------------------
- Updated all dependencies

2.1.4 - 2021-09-15
--------------------------------------------------------------------
- Updated all dependencies
- D8CORE-4699: Update diseval.so for PHP 7.4 (#242)
- D8CORE-4553 Increase media recurion limit to allow reuse of media items (icons)

2.1.3 - 2021-07-21
--------------------------------------------------------------------
- Updated all dependencies

2.1.2 - 2021-06-23
--------------------------------------------------------------------
- Updated all dependencies using Composer 2
- D8CORE-4185 Updated saml sign on endpoints

2.1.1 - 2021-05-20
--------------------------------------------------------------------
- Updated all dependencies.
- Updated paranoia patch & dependency.
- Modify the `deployment_identifier` value to use the ACSF paths for the Drupal container cache.

2.1.0 - 2021-04-21
--------------------------------------------------------------------
- Updated all dependencies.
- Adjustments to circleci cacheing
- Allowed custom roles to be created via the UI.

2.0.1 - 2021-03-17
--------------------------------------------------------------------
- Updated all dependencies.
- Removed acquia connector module.
- Added post staging update factory hook.

1.5.2 - 2020-12-11
--------------------------------------------------------------------
- D8CORE-2599 Fix double clicking links in ckeditor with double modals
- Updated Drupal core and dependencies

1.5.1 - 2020-11-18
--------------------------------------------------------------------
- Updated Drupal core for sa-core-2020-012

1.5.0 - 2020-11-09
--------------------------------------------------------------------
- Updated all dependencies
- D8CORE-1842 -- Tag-and-Release via CircleCI (#206)
- D8CORE-2933 Added missing ultimate cron jobs (#204)
- Require drush 10.

1.4.3 - 2020-10-06
--------------------------------------------------------------------
- Updated all dependencies
- Disable domain 301 redirect config for non-prod environments.

1.4.1 - 2020-09-15
--------------------------------------------------------------------
- Updated `stanford_profile` and `react_paragraphs` for a hotfix to unpublished content.

1.4.0 - 2020-09-14
--------------------------------------------------------------------
- Updated `stanford_profile` version to include `react_paragraphs` 2, embeddable media
- Updated tugboat configuration
- Removed patch for `drupal/paragraphs`

1.3.2 - 2020-08-07
--------------------------------------------------------------------
- D8CORE-1828 Allow changes to GTM configs
- DEVOPS-000: Remove field formatter patch
- D8CORE-2414: Update diseval library for PHP 7.3

1.3.1 - 2020-07-14
--------------------------------------------------------------------
- Updated patch for fake objects
- D8CORE-2205 updated config_ignore and system.theme config
- added acsf_theme_whitelist.txt
- Module cleanup
- Updated behat tests to codeception

1.3.0 - 2020-06-18
--------------------------------------------------------------------
- Added group option to codeception tests
- Updated Drupal Core to 8.9
- Updated Stanford Profile to 8.1.13
- Added ui_patterns patch for views ajax issues
- Added environment config for acsf.settings
- Updated `raid` for new ACSF settings
- Added environment variable STANFORD_ENCRYPT to globals
- Updated defaults for lando/example.env

1.2.0 - 2020-05-15
--------------------------------------------------------------------
- Fixed bugs with dependency update tasks
- Updated PR templates
- D8CORE-1217: Set up Codeception tests for Stanford Profile
- D8CORE-1858: Moved secrets to secrets.settings.php

1.1.0 - 2020-04-17
--------------------------------------------------------------------
- Added more paths for circleci cache mechanism (#125)
- SMPI-5 fixup: environment variables used to set token via circleci step (#126)
- Many tweaks to the dependency update process (#126)(#130)(#135)
- Dependency updates (#124)
- Updated composer.json for devel modules (#132)
- D8CORE-1706 Removed focal_point patches to be managed by stanford_media (#136)
- Removed `nobots` dependency from stack. (#138)
- D8CORE-1490: Patched CKEditor for anchor link id parsing. (#133)
- D8CORE-1475: Switched to dev dependencies process (#139)
- D8CORE-580: Added test for HSTS (#105)


1.0.1 - 2020-03-26
--------------------------------------------------------------------
- D8CORE-000: Lando troubleshooting documentation (#96)
- D8CORE-1405 Patched core hal module for alt support in default content (#99)
- Enabled acsf config split for AH environments (#94)
- Updated global.settings.php (#106)
- D8CORE-1521 D8CORE-1528 Updated README and blt aliases command (#108)
- D8CORE-000: Updated readme for multiple lando implementations (#110)
- SMPI-13: Added BLT functionality to post to sws-release-notifications Slack channel (#104)
- 8CORE-1479: Blocked access to vulnerability paths (#97)
- D8CORE-1689 Patched paragraphs module (#117)
- SMPI-5: Added automated dependency updates via CircleCI (#101)
- D8CORE-1549 Added blt command to clear domain 301 redirect settings (#118)
- D8CORE-1351: Added Paranoia (#98)
- Gitattributes update (#122)
- D8CORE-1526: Moved cors config to new place. (#121)
- D8CORE-1523: Added redirect for sso/login to htaccess (#113)
- Updated dependencies & Fixed dependency conflicts

1.0.0 - 2020-02-27
--------------------------------------------------------------------
- DCORE-1370: Alter menu form and status messages for config_readonly users (#88)
- D8CORE - Certificate location fix. (#89)


1.0.0-RC5 - 2020-02-21
--------------------------------------------------------------------
- D8CORE-1220 Added acsf-init-verify to circleci (#81)
- D8CORE-1229: Secure cookies with SimpleSAMLPHP authentication. (#80)
- Allow CORS on local environments (#83)
- Updated ckeditor plugins (#84)


1.0.0-RC4 - 2020-02-18
--------------------------------------------------------------------
- Changed the file temporary directory to a shared directory for load balancers

1.0.0-RC3 - 2020-02-14
--------------------------------------------------------------------

```
+------------------------------+---------------+---------------+-----------------------------------------------------------------------------------------+
| Production Changes           | From          | To            | Compare                                                                                 |
+------------------------------+---------------+---------------+-----------------------------------------------------------------------------------------+
| drupal/core                  | 8.8.1         | 8.8.2         | https://github.com/drupal/core/compare/8.8.1...8.8.2                                    |
| drupal/inline_entity_form    | 1.0.0-rc2     | 1.0.0-rc3     | https://git.drupalcode.org/project/inline_entity_form/compare/8.x-1.0.0-r...8.x-1.0.0-r |
| drupal/paragraphs            | dd59d0d       | f5d7954       | https://git.drupalcode.org/project/paragraphs/compare/8.x-dd59d...8.x-f5d79             |
| egulias/email-validator      | 2.1.15        | 2.1.17        | https://github.com/egulias/EmailValidator/compare/2.1.15...2.1.17                       |
| nette/neon                   | v3.1.0        | v3.1.1        | https://github.com/nette/neon/compare/v3.1.0...v3.1.1                                   |
| su-sws/jumpstart_ui          | 8.1.0         | 8.1.1         | https://github.com/SU-SWS/jumpstart_ui/compare/8.1.0...8.1.1                            |
| su-sws/react_paragraphs      | 8359115       | 8.1.0         | https://github.com/SU-SWS/react_paragraphs/compare/8359115...8.1.0                      |
| su-sws/stanford_basic        | 8.4.0         | 8.4.1         | https://github.com/SU-SWS/stanford_basic/compare/8.4.0...8.4.1                          |
| su-sws/stanford_media        | 8.2.0-alpha.2 | 8.2.0-alpha.3 | https://github.com/SU-SWS/stanford_media/compare/8.2.0-alpha.2...8.2.0-alpha.3          |
| su-sws/stanford_profile      | 468f102       | 8.1.1         | https://github.com/SU-SWS/stanford_profile/compare/468f102...8.1.1                      |
| su-sws/stanford_text_editor  | 8.1.1         | 8.1.2         | https://github.com/SU-SWS/stanford_text_editor/compare/8.1.1...8.1.2                    |
| symfony/polyfill-ctype       | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-ctype/compare/v1.13.1...v1.14.0                     |
| symfony/polyfill-iconv       | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-iconv/compare/v1.13.1...v1.14.0                     |
| symfony/polyfill-intl-idn    | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-intl-idn/compare/v1.13.1...v1.14.0                  |
| symfony/polyfill-mbstring    | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-mbstring/compare/v1.13.1...v1.14.0                  |
| symfony/polyfill-php56       | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-php56/compare/v1.13.1...v1.14.0                     |
| symfony/polyfill-php70       | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-php70/compare/v1.13.1...v1.14.0                     |
| symfony/polyfill-php72       | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-php72/compare/v1.13.1...v1.14.0                     |
| symfony/polyfill-util        | v1.13.1       | v1.14.0       | https://github.com/symfony/polyfill-util/compare/v1.13.1...v1.14.0                      |
| drupal/confirm_leave         | NEW           | 1.0.0-beta3   |                                                                                         |
| drupal/field_formatter_class | NEW           | e7b3c4a       |                                                                                         |
+------------------------------+---------------+---------------+-----------------------------------------------------------------------------------------+

+--------------------------------+---------+---------+-------------------------------------------------------------------------+
| Dev Changes                    | From    | To      | Compare                                                                 |
+--------------------------------+---------+---------+-------------------------------------------------------------------------+
| composer/spdx-licenses         | 1.5.2   | 1.5.3   | https://github.com/composer/spdx-licenses/compare/1.5.2...1.5.3         |
| seld/phar-utils                | 1.0.2   | 1.1.0   | https://github.com/Seldaek/phar-utils/compare/1.0.2...1.1.0             |
| su-sws/stanford-caravan        | 0649a11 | 8c982fc | https://github.com/SU-SWS/stanford-caravan/compare/0649a11...8c982fc    |
| webflo/drupal-core-require-dev | 8.8.1   | 8.8.2   | https://github.com/webflo/drupal-core-require-dev/compare/8.8.1...8.8.2 |
+--------------------------------+---------+---------+-------------------------------------------------------------------------+
```
