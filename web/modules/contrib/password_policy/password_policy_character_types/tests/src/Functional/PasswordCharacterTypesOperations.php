<?php

namespace Drupal\Tests\password_policy_character_types\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests password character types operations.
 *
 * @group password_policy_character_types
 */
class PasswordCharacterTypesOperations extends BrowserTestBase {

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
    'password_policy_character_types',
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
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the management of the "character_types" constraint.
   */
  public function testPasswordCharacterTypesManagement() {
    // Create a policy and add a "character_types" constraint.
    $this->drupalGet('admin/config/security/password-policy/add');
    $this->submitForm(['label' => 'Test policy', 'id' => 'test_policy'], 'Save');
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/character_types');
    $this->assertSession()->pageTextContains('Minimum number of character types');

    $this->submitForm(['character_types' => 2], 'Save');
    $this->drupalGet('/admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Minimum password character types: 2');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/character_types');
    $this->submitForm(['character_types' => 3], 'Save');
    $this->drupalGet('/admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Minimum password character types: 3');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/character_types');
    $this->submitForm(['character_types' => 4], 'Save');
    $this->drupalGet('/admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Minimum password character types: 4');
  }

}
