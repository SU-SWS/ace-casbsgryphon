<?php

declare(strict_types=1);

namespace Drupal\CasbsDrush\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class CasbsDrushCommands extends DrushCommands {

  #[CLI\Command(name: 'casbs')]
  public function casbs() {}

}
