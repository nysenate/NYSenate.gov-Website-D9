<?php

namespace Drupal\Tests\password_policy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the password policy status is shown alongside the password.
 *
 * @group password_policy
 */
class PasswordPolicyStatusVisibilityTest extends BrowserTestBase {

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
    'password_policy_test',
    'node',
  ];

  /**
   * Tests the visibility of the password policy status with no password field.
   */
  public function testHiddenPasswordField() {
    // Create user with permission to create policy.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);

    $this->drupalLogin($user);

    // Create role.
    $rid1 = $this->drupalCreateRole([]);

    // Set role for user.
    $edit = [
      'roles[' . $rid1 . ']' => $rid1,
    ];
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
    ];
    // Set reset and policy info.
    $this->submitForm($edit, 'Save');
    // Fill out length constraint for test policy.
    $edit = [
      'character_length' => '5',
      'character_operation' => 'minimum',
    ];
    // @todo convert this to using the button on the form.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_length');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('/admin/config/security/password-policy/test');
    $edit = [
      'roles[' . $rid1 . ']' => $rid1,
    ];
    $this->submitForm($edit, 'Save');

    // By default both password fields should be visible, and the password
    // policy status table as well.
    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertSession()->fieldExists('pass[pass1]');
    $this->assertSession()->fieldExists('pass[pass2]');
    $this->assertSession()->elementExists('css', '#password-policy-status');

    // If a module hides the password fields, then the password policy status
    // should be hidden as well.
    // @see password_policy_test_form_user_form_alter()
    \Drupal::service('state')->set('password_policy_test.user_form.hide_password', TRUE);

    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertSession()->fieldNotExists('pass[pass1]');
    $this->assertSession()->fieldNotExists('pass[pass2]');
    $this->assertSession()->elementNotExists('css', '#password-policy-status');
  }

  /**
   * Test the show policy table option.
   */
  public function testShowPolicyTableOption() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);

    $this->drupalLogin($user1);

    // Create role.
    $rid1 = $this->drupalCreateRole([]);

    // Set role for user.
    $edit = [
      'roles[' . $rid1 . ']' => $rid1,
    ];
    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
      'show_policy_table' => TRUE,
    ];
    // Set reset and policy info.
    $this->submitForm($edit, 'Save');
    // Fill out length constraint for test policy.
    $edit = [
      'character_length' => '5',
      'character_operation' => 'minimum',
    ];
    // @todo convert this to using the button on the form.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_length');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('/admin/config/security/password-policy/test');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid1 . ']' => $rid1,
    ];
    $this->submitForm($edit, 'Save');

    // Since the password policy should apply to the current user,
    // The status table should be shown.
    $this->drupalGet($user1->toUrl('edit-form'));
    $this->assertSession()->elementExists('css', '#password-policy-status');

    // Create user with permission to create policy.
    $user2 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);

    $this->drupalLogin($user2);

    $rid2 = $this->drupalCreateRole([]);

    // Set role for user.
    $edit = [
      'roles[' . $rid2 . ']' => $rid2,
    ];
    $this->drupalGet('user/' . $user1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Since the password policy should not apply to the current user,
    // The status table should be hidden.
    $this->drupalGet($user2->toUrl('edit-form'));
    $this->assertSession()->elementNotExists('css', '#password-policy-status');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test2',
      'label' => 'test2',
      'password_reset' => '1',
      'show_policy_table' => FALSE,
    ];
    // Set reset and policy info.
    $this->submitForm($edit, 'Save');
    // Fill out length constraint for test policy.
    $edit = [
      'character_length' => '5',
      'character_operation' => 'minimum',
    ];
    // @todo convert this to using the button on the form.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test2/password_length');
    $this->submitForm($edit, 'Save');

    // Since the password policy should not apply to the current user, because
    // the show_policy_table was set to FALSE, the status table should be
    // hidden.
    $this->drupalGet($user2->toUrl('edit-form'));
    $this->assertSession()->elementNotExists('css', '#password-policy-status');
  }

}
