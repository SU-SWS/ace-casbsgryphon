<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

$settings['config_sync_directory'] = DRUPAL_ROOT . '/profiles/custom/stanford_profile/config/sync';

if (EnvironmentDetector::isAhEnv()) {
  // Force HTTPS detection on Acquia
  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
  }
  
  // Set the base URL to use HTTPS
  if (isset($_SERVER['HTTP_HOST'])) {
    $base_url = 'https://' . $_SERVER['HTTP_HOST'];
    $GLOBALS['base_url'] = $base_url;
  }
}