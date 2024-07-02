<?php

namespace Drupal\Tests\password_policy_consecutive\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the password consecutive characters constraint.
 *
 * @group password_policy_consecutive
 */
class PasswordConsecutiveCharactersOperationsTest extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable at the start of the test.
   *
   * @var array
   */
  protected static $modules = [
    'password_policy_consecutive',
    'password_policy',
  ];

  /**
   * Administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));
  }

  /**
   * Test the management of the "password_policy_consecutive" constraint.
   */
  public function testPasswordConsecutiveConstraintManagement() {
    $web_assert = $this->assertSession();

    // Create a policy and add a "consecutive" constraint.
    $this->drupalGet('admin/config/security/password-policy/add');
    $this->submitForm(['label' => 'Test policy', 'id' => 'test_policy'], 'Save');
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/consecutive');
    $web_assert->pageTextContains('Consecutive identical characters fewer than');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/consecutive');
    $this->submitForm(['max_consecutive_characters' => 2], 'Save');
    $this->drupalGet('/admin/config/security/password-policy/test_policy');
    $web_assert->pageTextContains('No consecutive identical characters are allowed.');
  }

}
