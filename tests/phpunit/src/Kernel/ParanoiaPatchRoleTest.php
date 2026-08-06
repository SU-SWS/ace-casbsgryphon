<?php

namespace Drupal\Tests\lelandd8\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * @group user
 */
class ParanoiaPatchRoleTest extends KernelTestBase {

  public static $modules = ['system', 'user', 'paranoia'];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  public function testAdminProperty() {
    // Need to create the administrator role first so that it gets modified when
    // the test_role gets saved.
    $adminrole = Role::create(['id' => 'administrator']);
    $adminrole->save();
    $testrole = Role::create(['id' => 'test_role']);
    $testrole->SetIsAdmin(TRUE);
    $testrole->save();
    // The patch that we applied to Paranoia should catch that another role is
    // being saved as admin, reverse that, and apply it to "administrator".
    $testrole = Role::load('test_role');
    $this->assertFalse($testrole->isAdmin());
    $adminrole = Role::load("administrator");
    $this->assertTrue($adminrole->isAdmin());
  }

}
