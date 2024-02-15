<?php

namespace Drupal\Tests\password_policy_length\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests password length operations.
 *
 * @group password_policy_length
 */
class PasswordLengthOperations extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['password_policy_length', 'password_policy'];

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
   * Test the management of the "length" constraint.
   */
  public function testPasswordLengthManagement() {
    // Create a policy and add minimum and maximum "length" constraints.
    $this->drupalGet('admin/config/security/password-policy/add');
    $this->submitForm(['label' => 'Test policy', 'id' => 'test_policy'], 'Save');
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->assertSession()->pageTextContains('Number of characters');
    $this->assertSession()->pageTextContains('Operation');

    $this->submitForm([
      'character_operation' => 'minimum',
      'character_length' => 1,
    ], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Password character length of at least 1');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_operation' => 'maximum',
      'character_length' => 6,
    ], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Password character length of at most 6');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_operation' => 'minimum',
      'character_length' => '',
    ], 'Save');
    $this->assertSession()->pageTextContains('The character length must be a positive number.');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_operation' => 'minimum',
      'character_length' => -1,
    ], 'Save');
    $this->assertSession()->pageTextContains('The character length must be a positive number.');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_operation' => 'minimum',
      'character_length' => $this->randomMachineName(),
    ], 'Save');
    $this->assertSession()->pageTextContains('The character length must be a positive number.');
  }

}
