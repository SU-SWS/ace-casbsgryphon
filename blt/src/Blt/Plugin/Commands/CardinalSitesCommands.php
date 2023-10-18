<?php

namespace CardinalSites\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Common\EnvironmentDetector;
use GuzzleHttp\Client;
use Robo\ResultData;

/**
 * Class CardinalSitesCommands.
 *
 * @package CardinalSites\Blt\Plugin\Commands
 */
class CardinalSitesCommands extends BltTasks {

  /**
   * ACSF username.
   *
   * @var string
   */
  protected $acsfUsername;

  /**
   * ACSF API key.
   *
   * @var string
   */
  protected $acsfKey;

  /**
   * @hook pre-command drupal:update
   */
  public function preDrupalUpdate() {
    if (EnvironmentDetector::isAhEnv()) {
      $this->taskDrush()
        ->drush('config:set')
        ->arg('simplesamlphp_auth.settings')
        ->arg('activate')
        ->arg('0')
        ->drush('sql:query')
        ->arg('truncate sessions')
        ->run();
    }
  }

  /**
   * Sync down testing data from amptesting.
   *
   * @command cardinalsites:site-sync
   *
   * @param string $site
   *   Drush alias of site to sync.
   * @param string $environment
   *   Environment to sync from.
   *
   * @option $with-files Include files with the sync
   */
  public function siteSync(string $site = 'amptesting', string $environment = '01live', array $options = ['with-files' => FALSE]) {

    // Check for alias existing using `drush sa`. If alias doesn't exist, fetch it and check again.
    $alias = "@$site.$environment";
    $aliases = $this->taskDrush()
      ->drush("sa $alias")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    if (!str_contains($aliases, $alias)) {
      $this->invokeCommand('recipes:aliases:init:acquia');

      $aliases = $this->taskDrush()
        ->drush("sa $alias")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (!str_contains($aliases, $alias)) {
        return new ResultData(1, "Could not find that site alias");
      }
    }

    $db_sync = $this->taskDrush()
      ->drush("sql:sync @$site.$environment @self")
      ->run();
    if ($options['with-files']) {
      $file_sync = $this->taskDrush()
        ->drush("rsync @$site.$environment:%files @self:%files")
        ->option('exclude-paths', 'styles:css:js')
        ->run();
    }
    try {
      $this->invokeCommand('drupal:update');
    }
    catch (\Exception $e) {
      // In some circumstances, composer.lock file might be old.
      $this->taskComposerUpdate()->run();
      try {
        $this->invokeCommand('drupal:update');
      }
      catch (\Exception $e) {
        return new ResultData(1, "Failed running drupal:update.");
      }
    }

    if ($db_sync->wasSuccessful() && (!empty($file_sync) && $file_sync->wasSuccessful())) {
      return new ResultData(0, "Site sync successful.");
    }
    return new ResultData(1, "Site sync did not succeeed.");
  }

  /**
   * Re-stage all test sites from production down to the test environment.
   *
   * @command cardinalsites:stage-sites
   *
   * @param string $acsf_username
   *   ACSF username.
   * @param string $acsf_key
   *   ACSF API Key.
   * @param array $options
   *   Keyed array of options.
   *
   * @option dry-run Show the list of site ids that would be staged.
   */
  public function stageSites(string $acsf_username, string $acsf_key, $to_env = 'test', array $options = [
    'dry-run' => FALSE,
    'wipe-target-environment' => FALSE,
  ]
  ) {
    $this->acsfUsername = $acsf_username;
    $this->acsfKey = $acsf_key;

    // Sites for stack 1, `cardinalsites`.
    $site_ids = $this->getSiteIdsForStack($to_env);
    // Merge with sites for stack 4, `lelandd8`.
    $site_ids = array_merge($site_ids, $this->getSiteIdsForStack($to_env, 4));

    if ($options['dry-run']) {
      $this->say(count($site_ids) . ' will be staged.');
      $this->say("The follow sites would have been staged to the $to_env environment: " . implode(', ', $site_ids));
      return;
    }

    $body = [
      'to_env' => $to_env,
      'sites' => array_filter($site_ids, [$this, 'prodSiteExists']),
      'detailed_status' => FALSE,
      'wipe_target_environment' => $options['wipe-target-environment'],
      'synchronize_all_users' => FALSE,
    ];

    $client = new Client();
    $response = $client->post('https://www.cardinalsites.acsitefactory.com/api/v2/stage', [
      'json' => $body,
      'auth' => [$this->acsfUsername, $this->acsfKey],
    ]);
    $this->say((string) $response->getBody());
  }

  /**
   * Get a list of site ids on the test environment for the given stack.
   *
   * @param int $stack_id
   *   ACSF stack id.
   *
   * @return array
   *   List of site IDs.
   */
  protected function getSiteIdsForStack($to_env, int $stack_id = 1) {
    $client = new Client();
    $base_url = "https://www.$to_env-cardinalsites.acsitefactory.com/api/v1/sites";
    $response = $client->get("$base_url?limit=1&stack_id=$stack_id", [
      'auth' => [$this->acsfUsername, $this->acsfKey],
    ]);
    $response = json_decode((string) $response->getBody(), TRUE);
    $num_per_page = 50;
    $site_ids = [];
    for ($page = 1; $page <= ceil($response['count'] / $num_per_page); $page++) {
      $response = $client->get("$base_url?limit=$num_per_page&stack_id=$stack_id&page=$page", [
        'auth' => [$this->acsfUsername, $this->acsfKey],
      ]);
      $response = json_decode((string) $response->getBody(), TRUE);
      foreach ($response['sites'] as $site) {
        $site_ids[] = (int) $site['id'];
      }
    }
    return $site_ids;
  }

  /**
   * Check if the given site id currently exists on the prod environment.
   *
   * @param int $site_id
   *   Acquia site id.
   *
   * @return bool
   *   True if it does exist.
   */
  protected function prodSiteExists(int $site_id) {
    $client = new Client();
    $base_url = 'https://www.cardinalsites.acsitefactory.com/api/v1/sites';
    try {
      $response = $client->get("$base_url/$site_id", [
        'auth' => [$this->acsfUsername, $this->acsfKey],
      ]);
      return is_array(json_decode((string) $response->getBody(), TRUE));
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Refresh a theme for a given URL.
   *
   * @command cardinalsites:refresh-theme
   *
   * @param string $acsf_username
   *   The ACSF username for the API.
   * @param string $acsf_key
   *   The secret key for the API.
   * @param string $domain
   *   The domain you wish to refresh, without the protocol.
   */
  public function refreshTheme(string $acsf_username, string $acsf_key, string $domain) {
    $this->acsfUsername = $acsf_username;
    $this->acsfKey = $acsf_key;

    $site_info = $this->getSiteIdFromDomain($domain);

    $refresh_request = $this->refreshThemeRepo($site_info['site_id']);
    if ($refresh_request['accepted']) {
      $this->say('Theme repository refreshed.');
      $alias = $site_info['site_shortname'] . ".01live";
      $this->say('Clearing caches for ' . $alias);
      $this->taskDrush()
        ->alias($alias)
        ->drush("cr")
        ->run();
      $this->say('Cache Clear complete. ' . $domain . ' should now have updated the theme.');
    }
    else {
      throw new \Exception('Theme repository refresh failed.');
    }

  }

  /**
   * Re-stage custom theme repos for a given site_id via the ACSF API.
   *
   * @param int $site_id
   *   Acquia site id.
   *
   * @return array
   *   An array of the response data.
   */
  protected function refreshThemeRepo(int $site_id): array {
    $client = new Client();
    $base_url = 'https://www.cardinalsites.acsitefactory.com/api/v1/sites/' . $site_id . '/external-theme-refresh';

    $response = $client->post($base_url, [
      'auth' => [$this->acsfUsername, $this->acsfKey],
    ]);
    return json_decode((string) $response->getBody(), TRUE);

  }

  /**
   * Get a site_id for a given domain name.
   *
   * @param string $domain
   *   The domain you wish to search for, without the protocol.
   *
   * @return array|void
   *   An array containing the site_id and the site_shortname
   *
   * @throws Exception
   */
  protected function getSiteIdFromDomain(string $domain): array {
    $client = new Client();
    $base_url = 'https://www.cardinalsites.acsitefactory.com/api/v1/sites?domain_contains=' . $domain;

    $response = $client->get($base_url, [
      'auth' => [$this->acsfUsername, $this->acsfKey],
    ]);
    $site_data = json_decode((string) $response->getBody(), TRUE);
    if ($site_data['count'] == 1) {
      $site_id = $site_data['sites'][0]['id'];
      $site_shortname = $site_data['sites'][0]['site'];
      return [
        'site_id' => $site_id,
        'site_shortname' => $site_shortname,
      ];
    }
    else {
      throw new \Exception('Could not locate the site based on the provided url.');
    }
  }

}
