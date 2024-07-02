<?php

namespace Drupal\Tests\session_limit\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines a base class for testing the Security.txt module.
 *
 * @group securitytxt
 */
abstract class SessionLimitTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   *  Modules which should be enabled by default.
   */
  protected static $modules = ['session_limit'];

  /**
   * User with no permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authenticatedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->authenticatedUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
  }

}
