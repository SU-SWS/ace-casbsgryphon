<?php

use Faker\Factory;

/**
 * Class BlockPathsCest.
 *
 * @group stack
 */
class BlockPathsCest {

  /**
   * Wordpress urls should be blocked.
   */
  public function testWordPressPaths(AcceptanceTester $I) {
    $faker = Factory::create();

    $urls = [
      '/wp-admin/' . $faker->word,
      '/wp-admin/index.php',
      '/wp-config.php',
      '/wp-content/plugins/' . $faker->word,
      '/wp-content/plugins/hello.php',
      '/wp-content/plugins/index.php',
      '/wp-cron.php',
      '/wp-includes/' . $faker->word,
      '/wp-includes/query.php',
      '/wp-login.php',
      '/wp-signup.php',
    ];
    $this->checkUrls($I, $urls);
  }

  /**
   * Various paths should be blocked.
   */
  public function testVariousPaths(AcceptanceTester $I) {
    $urls = [
      '/adm/liveeditor/assetmanager/server/upload.php',
      '/adm/scripts/elfinder/connectors/php/connector.minimal.php',
      '/adm/uddi/uddilistener/vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php',
      '/adm/ujadmin/vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php',
      '/js/jQuery/uploadify/uploadify.php',
      '/plugins/vendor/phpunit/phpunit/src/Util/PHP/sssp.php.fla',
      '/plugins/vendor/phpunit/phpunit/src/Util/PHP/sssp.php.pjpeg',
      '/typo3/vendor/phpunit/phpunit/src/Util/PHP/sssp.phtml',
      '/uddilistener/vendor/phpunit/phpunit/src/Util/PHP/sssp.pHp5',
      '/uddilistener/vendor/phpunit/phpunit/src/Util/PHP/sssp.phtml',
      '/xmlrpc.php',
      self::getFakedPath() . '/eval-stdin.php',
      self::getFakedPath(2) . '/connector.minimal.php',
      self::getFakedPath(2) . '/sssp.pHp5',
      self::getFakedPath(2) . '/sssp.php.fla',
      self::getFakedPath(2) . '/sssp.php.pjpeg',
      self::getFakedPath(2) . '/sssp.phtml',
      self::getFakedPath(2) . '/uploadify.php',
      self::getFakedPath(3) . '/upload.php',
    ];
    $this->checkUrls($I, $urls);
  }

  /**
   * Loop through the urls and make sure they are 403 and non drupal.
   *
   * @param \AcceptanceTester $I
   *   Tester.
   * @param array $urls
   *   Array of relative urls.
   */
  protected function checkUrls(AcceptanceTester $I, array $urls) {
    foreach ($urls as $url) {
      $I->amOnPage($url);
      $I->canSeeResponseCodeIs(403);
      $I->cantSeeResponseHeader('X-Drupal-Cache');
    }
  }

  /**
   * Get a fake url path.
   *
   * @param int $num_words
   *   Number of words in the url.
   *
   * @return string
   *   Random faked url.
   */
  protected static function getFakedPath($num_words = 1) {
    $faker = Factory::create();
    $path = '';
    for ($i = 0; $i < $num_words; $i++) {
      $path .= '/' . $faker->word;
    }
    return $path;
  }

}
