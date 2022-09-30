<?php

namespace Drupal\Tests\password_policy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests password policy UI.
 *
 * @group password_policy
 */
class PasswordPolicyInterfaceTest extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'password_policy',
    'password_policy_length',
    'password_policy_character_types',
    'node',
  ];

  /**
   * Test failing password and verify it fails.
   */
  public function testOwnUserPasswords() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);

    $this->drupalLogin($user1);

    // Create role.
    $rid = $this->drupalCreateRole([]);

    // Set role for user.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
    ];
    $this->submitForm($edit, 'Save');
    // Fill out length constraint for test policy.
    $edit = [
      'character_length' => '5',
      'character_operation' => 'minimum',
    ];
    // @todo convert this to using the button on the form.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_length');
    $this->submitForm($edit, 'Save');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalGet('admin/config/security/password-policy/test');
    $this->submitForm($edit, 'Save');

    // Try failing password on form submit.
    $edit = [];
    $edit['current_pass'] = $user1->pass_raw;
    $edit['pass[pass1]'] = '111';
    $edit['pass[pass2]'] = '111';
    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('The password does not satisfy the password policies');

    // Try passing password on form submit.
    $edit = [];
    $edit['current_pass'] = $user1->pass_raw;
    $edit['pass[pass1]'] = '111111';
    $edit['pass[pass2]'] = '111111';
    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextNotContains('The password does not satisfy the password policies');
  }

}
