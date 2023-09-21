<?php

namespace Drupal\stanford_migrate\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Overrides the parent plugin to provide a method to get the active url.
 *
 * @package Drupal\stanford_migrate\Plugin\migrate_plus\data_parser
 * @deprecated in 8.4.3. Use Json instead.
 */
class StanfordJson extends Json {

  /**
   * Get the current active url string.
   *
   * @return string
   *   Url.
   *
   * @deprecated in 8.4.3 Use ::currentUrl() instead.
   */
  public function getCurrentUrl() {
    return $this->currentUrl();
  }

}
