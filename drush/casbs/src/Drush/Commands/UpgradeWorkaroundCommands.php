<?php

declare(strict_types=1);

namespace Drupal\CasbsDrush\Drush\Commands;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Commands\core\UpdateDBCommands;
use Drush\Commands\DrushCommands;

/**
 * One-time workarounds for the stanford_profile 13 upgrade (SWSDEVOPS-347).
 *
 * Why: on the first `drush updatedb` after moving to graphql 5.x, any update
 * hook that loads a graphql_server config entity
 * (graphql_compose_update_30000, ui_patterns_update_10202,
 * ui_patterns_library_update_10201, ...) can run before graphql_update_50001
 * and fatals with "Cannot assign null to property
 * Drupal\graphql\Entity\Server::$query_depth of type int". The update batch
 * then aborts and the deploy fails; only a second attempt succeeds.
 *
 * This is a Drush pre-command hook on `updatedb` so that it runs on every
 * update path without extra steps: `drush deploy` (which runs `updatedb` as a
 * subprocess), `drush sws:multisite:update[:parallel]` (which runs
 * `drush deploy` / `drush updb` per site), the CI `drush drupal:sync`, the
 * Acquia cloud hooks and local runs. It is discovered by Drush's PSR-4
 * command discovery through the root composer.json autoload entry
 * `Drupal\CasbsDrush\` => drush/casbs/src.
 *
 * Both steps only touch raw config / the update registry (never load the
 * graphql_server config entity, which is what fatals) and are guarded by the
 * installed schema versions, so they are no-ops once the site is updated.
 *
 * Remove after the first successful prod deploy of stanford_profile 13.x
 * (SWSDEVOPS-347).
 */
final class UpgradeWorkaroundCommands extends DrushCommands {

  /**
   * Prepare a pre-stanford_profile 13 database for `updatedb`.
   */
  #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: UpdateDBCommands::UPDATEDB)]
  public function preUpdatedb(): void {
    if (!class_exists(\Drupal::class) || !\Drupal::hasContainer()) {
      return;
    }

    /** @var \Drupal\Core\Update\UpdateHookRegistry $registry */
    $registry = \Drupal::service('update.update_hook_registry');
    $module_handler = \Drupal::moduleHandler();

    // 1. Same data fix as graphql_update_50001(): cast query_depth and
    // query_complexity to int on the raw config objects. graphql_update_50001
    // still runs afterwards and is then a no-op.
    if ($module_handler->moduleExists('graphql') && (int) $registry->getInstalledVersion('graphql') < 50001) {
      $config_factory = \Drupal::configFactory();
      foreach ($config_factory->listAll('graphql.graphql_servers.') as $name) {
        $server = $config_factory->getEditable($name);
        $changed = FALSE;
        foreach (['query_depth', 'query_complexity'] as $key) {
          if (!is_int($server->get($key))) {
            $server->set($key, (int) $server->get($key));
            $changed = TRUE;
          }
        }
        if ($changed) {
          $server->save(TRUE);
          $this->logger()->notice("SWSDEVOPS-347: normalized query_depth/query_complexity on $name.");
        }
      }
    }

    // 2. Upstream parity with the acsf-cardinalsites 7.0.3 post-staging hook
    // ("Temp fix to prevent update hook error that goes away after the first
    // attempt"): skip graphql_compose_update_30000. Its settings migration is
    // redone by stanford_profile_helper_post_update_graphql_compose() from the
    // sync directory and by the config import that follows.
    if ($module_handler->moduleExists('graphql_compose') && (int) $registry->getInstalledVersion('graphql_compose') < 30000) {
      $registry->setInstalledVersion('graphql_compose', 30000);
      $this->logger()->notice('SWSDEVOPS-347: marked graphql_compose_update_30000 as run.');
    }
  }

}
