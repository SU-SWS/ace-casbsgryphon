<?php

/**
 * @file
 * Config Page Config Overrides.
 */

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

// Do not redirect site on non-prod.
if (!EnvironmentDetector::isProdEnv()) {
  $config['config_pages.type.stanford_basic_site_settings']['third_party_settings']['config_pages_overrides']['39cd74f1-a878-442a-b8d6-ba1673a58065']['config_item'] = 0;
  $config['domain_301_redirect.settings']['enabled'] = 0;
}

$config['search_api.index.algolia_search']['read_only'] = !EnvironmentDetector::isProdEnv();
