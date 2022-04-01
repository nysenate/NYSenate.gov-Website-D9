<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test Login Security's user-blocking restrictions and default messages.
 *
 * @group login_security
 */
class LoginSecurityUserBlockingTest extends LoginSecurityTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'login_security', 'dblog'];

  /**
   * Bad users list.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $badUsers = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->badUsers[] = $this->drupalCreateUser();
    $this->badUsers[] = $this->drupalCreateUser();
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
    $variables = ['@attempt' => $attempt, '@login_attempts_limit' => $attempts_limit];

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
    $this->assertText('Your last login was', 'Last login message found.');
  }

  /**
   * Assert NO Text of last login message.
   */
  protected function assertNoTextLastLoginMessage() {
    $this->assertNoText('Your last login was', 'Last login message not found.');
  }

  /**
   * Assert Text of Last page access message.
   */
  protected function assertTextLastPageAccess() {
    $this->assertText('Your last page access (site activity) was ', 'Last page access message found.');
  }

  /**
   * Assert NO Text of Last page access message.
   */
  protected function assertNoTextLastPageAccess() {
    $this->assertNoText('Your last page access (site activity) was ', 'Last page access message not found.');
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
    $this->assertEqual(new FormattableMarkup($log->message, unserialize($log->variables)), $expected, 'User blocked log was set.');
    $this->assertEqual($log->severity, RfcLogLevel::NOTICE, 'User blocked log was of severity "Notice".');
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
   * Test threshold notify functionality.
   */
  public function testThresholdNotify() {
    // Set notify threshold to 5, and user locking to 5.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_wrong_count', 5)
      ->set('activity_threshold', 5)
      ->save();

    // Attempt 10 bad logins. Since the user will be locked out after 5, only
    // a single log message should be set, and an attack should not be
    // detected.
    for ($i = 0; $i < 10; $i++) {
      $login = [
        'name' => $this->badUsers[0]->getAccountName(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalPostForm('user', $login, $this->t('Log in'));
    }

    // Ensure a log message has been set.
    $logs = $this->getLogMessages();
    $this->assertEqual(count($logs), 1, '1 event was logged.');
    $log = array_pop($logs);
    $this->assertBlockedUser($log, $this->badUsers[0]->getAccountName());
    Database::getConnection()->truncate('watchdog')->execute();

    // Run failed logins as second user to trigger an attack warning.
    for ($i = 0; $i < 10; $i++) {
      $login = [
        'name' => $this->badUsers[1]->getAccountName(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalPostForm('user', $login, $this->t('Log in'));
    }

    $logs = $this->getLogMessages();

    // 2 logs should be generated.
    $this->assertEqual(count($logs), 2, '2 events were logged.');

    // First log should be the ongoing attack, triggered on attempt after the
    // threshold.
    $log = array_shift($logs);
    $variables = ['@activity_threshold' => 5, '@tracking_current_count' => 6];
    $expected = new FormattableMarkup('Ongoing attack detected: Suspicious activity detected in login form submissions. Too many invalid login attempts threshold reached: currently @tracking_current_count events are tracked, and threshold is configured for @activity_threshold attempts.', $variables);
    $this->assertEqual(new FormattableMarkup($log->message, unserialize($log->variables)), $expected);
    $this->assertEqual($log->severity, RfcLogLevel::WARNING, 'The logged alert was of severity "Warning".');

    // Second log should be a blocked user.
    $log = array_shift($logs);
    $this->assertBlockedUser($log, $this->badUsers[1]->getAccountName());
  }

  /**
   * Test user blocking.
   */
  public function testUserBlocking() {
    $config = \Drupal::configFactory()->getEditable('login_security.settings');

    $login_attempts_limit = 2;

    // Allow 2 attempts to login before being blocking is enforced.
    $config->set('user_wrong_count', $login_attempts_limit)->save();

    // We can drupalGetMails() to see if a notice went out to admin.
    // In the meantime, turn the message off just in case it doesn't get
    // caught properly yet.
    $config->set('user_blocked_notification_emails', '')->save();

    $normal_user = $this->drupalCreateUser();

    // Intentionally break the password to repeat invalid logins.
    $new_pass = user_password();
    $normal_user->setPassword($new_pass);

    $config->set('notice_attempts_available', 1)->save();

    // First try.
    $this->drupalLoginLite($normal_user);
    $this->assertText($this->getAttemptsAvailableMessage(1, $login_attempts_limit), 'Attempts available message displayed.');
    $this->assertFieldByName('form_id', 'user_login_form', 'Login form found.');

    // Turns off the warning message we looked for in the previous assert.
    $config->set('notice_attempts_available', 0)->save();

    // Second try.
    $this->drupalLoginLite($normal_user);
    $this->assertNoText($this->getAttemptsAvailableMessage(2, $login_attempts_limit), 'Attempts available message NOT displayed.');
    $this->assertFieldByName('form_id', 'user_login_form', 'Login form found.');

    // Turns back on the warning message we looked for in the previous assert.
    $this->assertText(new FormattableMarkup('The user @user_name has been blocked due to failed login attempts.', ['@user_name' => $normal_user->getAccountName()]), 'Blocked message displayed.');
    $this->assertFieldByName('form_id', 'user_login_form', 'Login form found.');
  }

  /**
   * Test disable core login error toggle.
   */
  public function testDrupalErrorToggle() {
    $config = \Drupal::configFactory()->getEditable('login_security.settings');

    $normal_user = $this->drupalCreateUser();

    // Intentionally break the password to repeat invalid logins.
    $new_pass = user_password();
    $normal_user->setPassword($new_pass);

    $config->set('disable_core_login_error', 0)->save();

    $this->drupalLoginLite($normal_user);
    $this->assertRaw($this->getDefaultDrupalLoginErrorMessage(), 'Drupal "...Have you forgotten your password?" login error message found.');

    // Block user.
    $normal_user->status->setValue(0);
    $normal_user->save();
    $this->drupalLoginLite($normal_user);
    $this->assertRaw($this->getDefaultDrupalBlockedUserErrorMessage($normal_user->getAccountName()), 'Drupal "...has not been activated or is blocked." login error message found.');

    $config->set('disable_core_login_error', 1)->save();

    // Unblock user.
    $normal_user->status->setValue(1);
    $normal_user->save();
    $this->drupalLoginLite($normal_user);
    $this->assertNoRaw($this->getDefaultDrupalLoginErrorMessage(), 'Drupal "...Have you forgotten your password?" login error message NOT found.');

    // Block user.
    $normal_user->status->setValue(0);
    $normal_user->save();
    $this->drupalLoginLite($normal_user);
    $this->assertNoRaw($this->getDefaultDrupalBlockedUserErrorMessage($normal_user->getAccountName()), 'Drupal "...has not been activated or is blocked." login error message NOT found.');
  }

  /**
   * Test login message.
   */
  public function testLoginMessage() {
    $config = \Drupal::configFactory()->getEditable('login_security.settings');

    $normal_user = $this->drupalCreateUser();

    $config->set('last_login_timestamp', 1)->save();
    $config->set('last_access_timestamp', 1)->save();

    $this->drupalLogin($normal_user);
    // This is the very first login ever, so there should be no previous login
    // to show.
    $this->assertNoTextLastLoginMessage();

    $config->set('last_login_timestamp', 0)->save();
    $config->set('last_access_timestamp', 0)->save();

    $this->drupalLogin($normal_user);
    $this->assertNoTextLastLoginMessage();
    $this->assertNoTextLastPageAccess();

    $config->set('last_login_timestamp', 1)->save();
    $this->drupalLogin($normal_user);
    $this->assertTextLastLoginMessage();
    $this->assertNoTextLastPageAccess();

    $config->set('last_login_timestamp', 0)->save();
    $config->set('last_access_timestamp', 1)->save();
    $this->drupalLogin($normal_user);
    $this->assertNoTextLastLoginMessage();
    $this->assertTextLastPageAccess();

    $config->set('last_login_timestamp', 1)->save();
    $this->drupalLogin($normal_user);
    $this->assertTextLastLoginMessage();
    $this->assertTextLastPageAccess();
  }

}
