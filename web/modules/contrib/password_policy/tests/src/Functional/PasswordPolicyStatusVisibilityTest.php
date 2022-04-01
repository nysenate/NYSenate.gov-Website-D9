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
  protected static $modules = ['password_policy', 'password_policy_test'];

  /**
   * Tests the visibility of the password policy status.
   */
  public function test() {
    // Create user with permission to create policy.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // By default both password fields should be visible, and the password
    // policy status table as well.
    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertFieldByName('pass[pass1]');
    $this->assertFieldByName('pass[pass2]');
    $this->assertElementPresent('#password-policy-status');

    // If a module hides the password fields, then the password policy status
    // should be hidden as well.
    // @see password_policy_test_form_user_form_alter()
    \Drupal::service('state')->set('password_policy_test.user_form.hide_password', TRUE);

    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertNoFieldByName('pass[pass1]');
    $this->assertNoFieldByName('pass[pass2]');
    $this->assertElementNotPresent('#password-policy-status');
  }

}
