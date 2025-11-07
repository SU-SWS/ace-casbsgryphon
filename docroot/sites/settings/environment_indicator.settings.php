<?php

use Drupal\SwsDrush\Helpers\EnvironmentDetector;

$config['environment_indicator.indicator']['bg_color'] = '#086601';
$config['environment_indicator.indicator']['fg_color'] = '#fff';
$config['environment_indicator.indicator']['name'] = getenv('CI') ? 'CI Env' : 'Local';

if (!EnvironmentDetector::isAhEnv()) {
  return;
}

if (EnvironmentDetector::isProdEnv()) {
  $config['environment_indicator.indicator']['bg_color'] = '#000';
  $config['environment_indicator.indicator']['fg_color'] = '#fff';
  $config['environment_indicator.indicator']['name'] = 'Production';
  return;
}

if (EnvironmentDetector::isStageEnv()) {
  $config['environment_indicator.indicator']['bg_color'] = '#4127C2';
  $config['environment_indicator.indicator']['fg_color'] = '#fff';
  $config['environment_indicator.indicator']['name'] = 'Staging';
  return;
}

if (EnvironmentDetector::isdevEnv()) {
  $config['environment_indicator.indicator']['bg_color'] = '#6B0500';
  $config['environment_indicator.indicator']['fg_color'] = '#fff';
  $config['environment_indicator.indicator']['name'] = 'Development';
  return;
}

// As site Factory only has dev/stage/prod environments at the moment, this is a catch all for future improvements
// where they hopefully add ODEs.
$config['environment_indicator.indicator']['bg_color'] = '#086601';
$config['environment_indicator.indicator']['fg_color'] = '#fff';
$config['environment_indicator.indicator']['name'] = EnvironmentDetector::getAhEnv();
