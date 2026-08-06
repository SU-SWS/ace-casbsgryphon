<?php

use Codeception\Attribute as CodeceptionAttribute;
use Codeception\Example;

/**
 * Class ParanoiaCest.
 *
 * @group stack
 */
#[CodeceptionAttribute\Group('paranoia')]
class ParanoiaCest {

  /**
   * Enable paranoia and log in as admin first.
   */
  public function _before(AcceptanceTester $I) {
    if (!\Drupal::moduleHandler()->moduleExists('paranoia')) {
      \Drupal::service('module_installer')->install(['paranoia']);
    }
  }

  /**
   * Enable paranoia and log in as admin first.
   */
  public function _after(AcceptanceTester $I) {
    if (\Drupal::moduleHandler()->moduleExists('paranoia')) {
      \Drupal::service('module_installer')->uninstall(['paranoia']);
    }
  }

  /**
   * Module should be hidden from enable/disable.
   */
  public function testRiskyModules(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/modules');
    $I->canSeeResponseCodeIs(200);
    $I->cantSee('Paranoia');
  }

  /**
   * User 1 can't be edited.
   */
  #[CodeceptionAttribute\Examples('administrator')]
  #[CodeceptionAttribute\Examples('site_manager')]
  public function testUserOne(AcceptanceTester $I, Example $example) {
    $I->logInWithRole($example[0]);
    $I->amOnPage('/user/1/edit');
    $I->canSee('You must log in as this user (user/1) to modify the name, email address, and password for this account.');
  }

  /**
   * The admin role can't be changed.
   */
  public function testAdminRoleLock(AcceptanceTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/config/people/accounts');
    $I->cantSee('Administrator role');
    $I->cantSee('This role will be automatically assigned new permissions whenever a module is enabled. Changing this setting will not affect existing permissions.');
    $I->cantSeeElement('select[name="user_admin_role"]');
  }

}
