<?php

class DemoCest {

  /**
   * Validate the homepage loads.
   */
  public function testHomepage(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->canSeeInCurrentUrl('/user/login');
    $I->canSeeResponseCodeIs(200);

    $I->amOnTheHomepage();
    $I->canSee('Stanford');
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure');
    $I->canSeeResponseCodeIs(200);
  }

}
