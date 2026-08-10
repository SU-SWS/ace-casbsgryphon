<?php

use Faker\Factory;

/**
 * Test the fast 404 module.
 */
class Fast404Cest {

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Test fast 404 page.
   *
   * @group fast404
   */
  public function testFast404(AcceptanceTester $I) {
    $path = $this->faker->words(2, TRUE);
    $path = preg_replace('/[^a-z]/', '-', strtolower($path));
    $I->amOnPage($path);
    $I->canSeeResponseCodeIs(404);

    $redirect_source = $this->faker->words(2, TRUE);
    $redirect_source = preg_replace('/[^a-z]/', '-', strtolower($redirect_source));

    $node = $I->createEntity([
      'type' => 'stanford_page',
      'title' => $this->faker->words(3, TRUE),
    ]);

    $I->createEntity([
      'redirect_source' => [
        [
          'path' => $redirect_source,
          'query' => [],
        ],
      ],
      'redirect_redirect' => [
        [
          'uri' => 'internal:/node/' . $node->id(),
          'options' => [],
        ],
      ],
      'status_code' => 301,
    ], 'redirect');
    $I->amOnPage($redirect_source);

    $I->canSeeResponseCodeIs(200);
    $I->canSeeInCurrentUrl($node->toUrl()->toString());
  }

}
