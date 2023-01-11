<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Helper test class with some added functions for testing.
 */
abstract class LoginSecurityTestBase extends BrowserTestBase {

  const ADMIN_SETTINGS_PATH = 'admin/config/people/login_security';

  /**
   * Modules needed for testing purposes.
   *
   * @var array
   *   Array of modules to enable for testing purposes.
   */
  protected static $modules = ['login_security'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure these tables have no entries.
    \Drupal::database()->query('TRUNCATE TABLE {login_security_track}');
    \Drupal::database()->query('TRUNCATE TABLE {ban_ip}');

    // Set time tracking window to 1 hour.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('track_time', 1)
      ->save();
  }

  /**
   * Returns a list containig the admin settings fields.
   */
  protected function getAdminUserSettingsFields() {
    return [
      'track_time',
      'user_wrong_count',
      'host_wrong_count',
      'host_wrong_count_hard',
      'notice_attempts_available',
      'notice_attempts_message',
      'host_soft_banned',
      'host_hard_banned',
      'user_blocked',
      'user_blocked_notification_emails',
      'user_blocked_email_subject',
      'user_blocked_email_body',
      'last_login_timestamp',
      'last_access_timestamp',
      'login_activity_notification_emails',
      'login_activity_email_subject',
      'login_activity_email_body',
      'activity_threshold',
    ];
  }

  /**
   * Returns the 'get attempts available' message.
   *
   * @param int $attempt
   *   The attempt count.
   * @param int $attempts_limit
   *   The attempts limit number.
   *
   * @return string
   *   Returns the related message.
   */
  protected function getAttemptsAvailableMessage($attempt, $attempts_limit) {
    $variables = [
      '@attempt' => $attempt,
      '@login_attempts_limit' => $attempts_limit,
    ];

    return new FormattableMarkup('You have used @attempt out of @login_attempts_limit login attempts. After all @login_attempts_limit have been used, you will be unable to login.', $variables);
  }

  /**
   * Returns the default Drupal Login error message.
   */
  protected function getDefaultDrupalLoginErrorMessage() {
    return 'Unrecognized username or password.';
  }

  /**
   * Returns the default Drupal Blocked User error message.
   */
  protected function getDefaultDrupalBlockedUserErrorMessage($user_name) {
    return new FormattableMarkup('The username %name has not been activated or is blocked.', ['%name' => $user_name]);
  }

  /**
   * Assert Text of last login message.
   */
  protected function assertTextLastLoginMessage() {
    $this->assertSession()->pageTextContains('Your last login was');
  }

  /**
   * Assert NO Text of last login message.
   */
  protected function assertNoTextLastLoginMessage() {
    $this->assertSession()->pageTextNotContains('Your last login was');
  }

  /**
   * Assert Text of Last page access message.
   */
  protected function assertTextLastPageAccess() {
    $this->assertSession()->pageTextContains('Your last page access (site activity) was ');
  }

  /**
   * Assert NO Text of Last page access message.
   */
  protected function assertNoTextLastPageAccess() {
    $this->assertSession()->pageTextNotContains('Your last page access (site activity) was ');
  }

  /**
   * Asserts a blocked user log was set.
   *
   * @param object $log
   *   The raw log record from the database.
   * @param string $username
   *   The blocked username.
   */
  protected function assertBlockedUser($log, $username) {
    $variables = ['@username' => $username];
    $expected = new FormattableMarkup('Blocked user @username due to security configuration.', $variables);
    $this->assertEquals(new FormattableMarkup($log->message, unserialize($log->variables)), $expected, 'User blocked log was set.');
    $this->assertEquals($log->severity, RfcLogLevel::NOTICE, 'User blocked log was of severity "Notice".');
  }

  /**
   * Retrieve log records from the watchdog table.
   *
   * @return array
   *   The log messages.
   */
  protected function getLogMessages() {
    return \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['wid', 'message', 'variables', 'severity'])
      ->condition('w.type', 'login_security')
      ->execute()
      ->fetchAllAssoc('wid');
  }

  /**
   * Checks if we are currently on the login screen.
   */
  protected function onLoginScreen() {
    $session = $this->assertSession();
    $session->pageTextContains('Log in');
    $session->elementExists('css', '#edit-name');
    $session->elementExists('css', '#edit-pass');
  }

  /**
   * Manual login without "drupalLogin" to check module functionality.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user to login.
   */
  protected function validLogin(User $user) {
    if ($this->drupalUserIsLoggedIn($user)) {
      $this->drupalLogout();
    }
    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-name', $user->getAccountName());
    $page->fillField('edit-pass', $user->passRaw);
    $page->pressButton('edit-submit');

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Login the user, using an invalid password.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user to login.
   */
  protected function invalidPwLogin(User $user) {
    if ($this->drupalUserIsLoggedIn($user)) {
      $this->drupalLogout();
    }
    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-name', $user->getAccountName());
    $page->fillField('edit-pass', 'not the correct password');
    $page->pressButton('edit-submit');
  }

  /**
   * Login a non existant user.
   */
  protected function invalidUsernameLogin() {
    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-name', 'Incorrect User');
    $page->fillField('edit-pass', 'not the correct password');
    $page->pressButton('edit-submit');
  }

}
