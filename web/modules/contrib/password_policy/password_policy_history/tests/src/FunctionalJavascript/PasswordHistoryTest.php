<?php

namespace Drupal\Tests\password_policy_history\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests password history.
 *
 * @group password_policy_history
 */
class PasswordHistoryTest extends WebDriverTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  public static $modules = ['password_policy', 'password_policy_history'];

  /**
   * Test history constraint.
   */
  public function testHistoryConstraint() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);
    $this->drupalLogin($user1);

    $user2 = $this->drupalCreateUser();

    // Create role.
    $rid = $this->drupalCreateRole([]);

    $user_path = sprintf('user/%s/edit', $user2->id());

    $this->drupalGet($user_path);

    $session = $this->getSession();
    $page = $session->getPage();

    // Set role for user. Also manually update password. The user insert hook
    // does not add a password hash in the password_policy_history table for
    // users on initial creation via drupalCreateUser(), but this password
    // update will register an entry since the password is updated in the
    // form instead.
    $page->fillField('pass[pass1]', $user2->pass_raw);
    $page->fillField('pass[pass2]', $user2->pass_raw);
    $page->checkField(sprintf('roles[%s]', $rid));
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $session = $this->getSession();
    $page = $session->getPage();

    $page->fillField('label', 'test');
    $label_field = $page->findField('label');
    $label_field->setValue('test');
    $label_field->blur();
    $this->assertSession()->waitForElementVisible('css', '.link');
    $page->fillField('password_reset', '1');
    $this->submitForm([], 'Next');

    $this->assertSession()->pageTextContains('No constraints have been configured.');

    // Fill out length constraint for test policy.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_policy_history_constraint');

    $session = $this->getSession();
    $page = $session->getPage();
    $page->fillField('history_repeats', '1');
    $this->submitForm([], 'Save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->responseContains('password_policy_history_constraint');
    $this->assertSession()->pageTextContains('Number of allowed repeated passwords: 1');

    // Set the roles for the policy.
    $this->drupalGet('admin/config/security/password-policy/test/roles');
    $session = $this->getSession();
    $page = $session->getPage();
    $page->checkField(sprintf('roles[%s]', $rid));
    $this->submitForm([], 'Finish');

    // Login as user2.
    $this->drupalLogin($user2);

    // Visit the user edit page.
    $this->drupalGet($user_path);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set a value for the pass[pass1] field.
    $pass_1_field = $page->findField('pass[pass1]');
    $pass_1_field->setValue($user2->pass_raw);
    // Remove focus from the password field so the onchange event is triggered.
    $pass_1_field->blur();

    $page->fillField('current_pass', $user2->pass_raw);
    $page->fillField('pass[pass2]', $user2->pass_raw);

    $this->assertSession()->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains('Password has been reused too many times. Choose a different password.');

    // Save the form so the password history updates.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Visit the user edit page.
    $this->drupalGet($user_path);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set a value for the pass[pass1] field.
    $pass_1_field = $page->findField('pass[pass1]');
    $pass_1_field->setValue($user2->pass_raw);
    // Remove focus from the password field so the onchange event is triggered.
    $pass_1_field->blur();

    $page->fillField('current_pass', $user2->pass_raw);
    $page->fillField('pass[pass2]', $user2->pass_raw);

    $this->assertSession()->assertWaitOnAjaxRequest();

    // The user shouldn't be able to change a password they used before.
    $assert_session->pageTextContains('Password has been reused too many times. Choose a different password.');

    // Attempt to save the form. Should not succeed.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('The password does not satisfy the password policies');
  }

}
