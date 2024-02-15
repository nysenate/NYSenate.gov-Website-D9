<?php

namespace Drupal\Tests\password_policy_characters\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests password character operations.
 *
 * @group password_policy_characters
 */
class PasswordCharacterOperations extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['password_policy_characters', 'password_policy'];

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
   * Test the management of the "characters" constraint.
   */
  public function testPasswordCharacterManagement() {
    // Create a policy and add various "characters" constraints.
    $this->drupalGet('admin/config/security/password-policy/add');
    $this->submitForm(['label' => 'Test policy', 'id' => 'test_policy'], 'Save');
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->assertSession()->pageTextContains('Number of characters');
    $this->assertSession()->pageTextContains('Character type');

    $this->submitForm(['character_type' => 'special', 'character_count' => 2], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Password must contain at least 2 unique special characters');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->submitForm(['character_type' => 'numeric', 'character_count' => 3], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Password must contain at least 3 unique numeric characters');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->submitForm(['character_type' => 'lowercase', 'character_count' => 4], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Password must contain at least 4 unique lowercase characters');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->submitForm(['character_type' => 'uppercase', 'character_count' => 5], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');
    $this->assertSession()->pageTextContains('Password must contain at least 5 unique uppercase characters');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->submitForm(['character_type' => 'special', 'character_count' => ''], 'Save');
    $this->assertSession()->pageTextContains('Number of characters field is required.');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->submitForm(['character_type' => 'special', 'character_count' => -1], 'Save');
    $this->assertSession()->pageTextContains('The number of characters must be a positive number.');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_policy_character_constraint');
    $this->submitForm([
      'character_type' => 'special',
      'character_count' => $this->randomMachineName(),
    ], 'Save');
    $this->assertSession()->pageTextContains('The number of characters must be a positive number.');
  }

}
