<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test Login Security's soft blocking restrictions.
 *
 * @group login_security
 */
class LoginSecuritySoftBlockTest extends LoginSecurityTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'login_security'];

  /**
   * Checks whether the request is not Soft Blocked.
   */
  protected function assertNoSoftBlocked($account) {
    $this->drupalLoginLite($account);
    $this->assertNoText('This host is not allowed to log in', 'Soft-blocked notice does not display.');
    $this->assertNoText(new FormattableMarkup('The user @user_name has been blocked due to failed login attempts.', ['@user_name' => $account->getAccountName()]), 'User is not blocked.');
    $this->assertFieldByName('form_id', 'user_login_form', 'Login form found.');
  }

  /**
   * Checks whether the request is Soft Blocked.
   */
  protected function assertSoftBlocked($account) {
    $this->drupalLoginLite($account);
    $this->assertText('This host is not allowed to log in', 'Soft-block message displays.');
    $this->assertFieldByName('form_id', 'user_login_form', 'Login form found.');
  }

  /**
   * Test login attempts.
   */
  public function testLogin() {
    // Set wrong count to 5 attempts.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_wrong_count', 5)
      ->save();

    $normal_user = $this->drupalCreateUser();
    $this->drupalLogin($normal_user);

    $warning_message = 'You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.';
    $this->assertNoText($warning_message, 'Attempts available message displayed.');
  }

  /**
   * Test soft blocking.
   */
  public function testSoftBlocking() {
    // Allow 3 attempts to login before being soft-blocking is enforced.
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    $config->set('user_wrong_count', 0)->save();
    $config->set('host_wrong_count', 2)->save();

    // Remove notices.
    $config->set('notice_attempts_available', 0)->save();

    $normal_user = $this->drupalCreateUser();
    $good_pass = $normal_user->getPassword();

    // Intentionally break the password to repeat invalid logins.
    $new_pass = user_password();
    $normal_user->setPassword($new_pass);

    // First try.
    $this->assertNoSoftBlocked($normal_user);

    // Second try.
    $this->assertNoSoftBlocked($normal_user);

    // Remove error messages display.
    $config->set('disable_core_login_error', 1)->save();

    // Third try, still valid without soft blocking.
    $this->assertNoSoftBlocked($normal_user);

    // Restore error messages.
    $config->set('disable_core_login_error', 0)->save();

    // 4th attempt, the host is not allowed this time.
    $this->assertSoftBlocked($normal_user);

    // Try a normal login because it should be locked out now.
    $normal_user->setPassword($good_pass);
    $this->assertSoftBlocked($normal_user);
  }

}
