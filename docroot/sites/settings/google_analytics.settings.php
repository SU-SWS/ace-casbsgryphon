<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

// Do not provide an account for GA on non-prod.
if (!EnvironmentDetector::isProdEnv()) {
  $config['google_analytics.settings']['account'] = '';
}
