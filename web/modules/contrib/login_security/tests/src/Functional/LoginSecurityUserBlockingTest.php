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
   * A user with admin permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $normalUser;

  /**
   * Another normal user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $secondUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'login_security', 'dblog', 'node'];

  /**
   * Bad users list.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $badUsers = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->badUsers[] = $this->drupalCreateUser();
    $this->badUsers[] = $this->drupalCreateUser();

    $this->normalUser = $this->drupalCreateUser([]);
    $this->secondUser = $this->drupalCreateUser([]);

    $this->createContentType(['type' => 'article']);
    $this->createNode([
      'type' => 'article',
      'id' => 1,
      'title' => 'test123',
    ]);
  }

  /**
   * Test normal login with the user_wrong_count set.
   */
  public function testNormalLogin() {
    // Set invalid login count to an abritary number (5 for example), just to
    // generally activate this option:
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_wrong_count', 5)
      ->set('notice_attempts_available', 1)
      ->save();

    $this->validLogin($this->normalUser);

    $warning_message = 'You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.';
    $this->assertSession()->pageTextNotContains($warning_message);
  }

  /**
   * Test threshold notify functionality.
   */
  public function testThresholdNotify() {
    $user_wrong_count = 5;
    $activity_threshold = 11;
    // Set notify threshold to 5, and user locking to 5.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_wrong_count', $user_wrong_count)
      ->set('activity_threshold', $activity_threshold)
      ->save();

    // Attempt 10 bad logins. Since the user will be locked out after 5, only
    // a single log message should be set, and an attack should not be
    // detected.
    for ($i = 0; $i < 10; $i++) {
      $login = [
        'name' => $this->badUsers[0]->getAccountName(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalGet('user');
      $this->submitForm($login, $this->t('Log in'));
    }

    // Ensure a log message has been set.
    $logs = $this->getLogMessages();
    $this->assertEquals(1, count($logs), '1 event was logged.');
    $log = array_pop($logs);
    $this->assertBlockedUser($log, $this->badUsers[0]->getAccountName());
    Database::getConnection()->truncate('watchdog')->execute();

    // Run failed logins as second user to trigger an attack warning.
    for ($i = 0; $i < 10; $i++) {
      $login = [
        'name' => $this->badUsers[1]->getAccountName(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalGet('user');
      $this->submitForm($login, $this->t('Log in'));
    }

    $logs = $this->getLogMessages();

    // 2 logs should be generated.
    $this->assertEquals(2, count($logs), '2 events were logged.');

    // Current count of entires should be 20:
    $currentVariables = _login_security_get_variables_by_name();
    $this->assertEquals(20, $currentVariables['@tracking_current_count']);

    // First log should be the ongoing attack, triggered on attempt after the
    // threshold.
    $log = array_shift($logs);
    $variables = [
      '@activity_threshold' => $activity_threshold,
      // The log notification message is created with the count of when
      // $tracking_current_count > $activity_threshold:
      '@tracking_current_count' => $activity_threshold + 1,
    ];
    $expected = new FormattableMarkup(
      'Ongoing attack detected: Suspicious activity detected in login form submissions. Too many invalid login attempts threshold reached: Currently @tracking_current_count events are tracked, and threshold is configured for @activity_threshold attempts.',
      $variables
    );
    $this->assertEquals($expected, new FormattableMarkup($log->message, unserialize($log->variables)));
    $this->assertEquals(RfcLogLevel::WARNING, $log->severity, 'The logged alert was of severity "Warning".');

    // Second log should be a blocked user.
    $log = array_shift($logs);
    $this->assertBlockedUser($log, $this->badUsers[1]->getAccountName());
  }

  // @codingStandardsIgnoreStart
  // @todo This doesn't work yet, see
  // https://www.drupal.org/project/login_security/issues/1230558
  // /**
  //  * Tests the remaining login attempt notice for the user lock option.
  //  */
  // public function testUserNoticeLogin() {
  //   $config = \Drupal::configFactory()->getEditable('login_security.settings');
  //   $config->set('user_wrong_count', 5)
  //     ->set('notice_attempts_available', 0)
  //     ->save();

  //   // Login with notices disabled:
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->pageTextNotContains('You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.');
  //   // Login with notices enabled:
  //   $config->set('notice_attempts_available', 1)->save();
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->pageTextContains('You have used 2 out of 5 login attempts. After all 5 have been used, you will be unable to login.');
  // }

  /**
   * Test user blocking.
   */
  public function testBlocking() {
    $session = $this->assertSession();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Allow 2 attempts to login before being soft-blocking is enforced:
    $config->set('user_wrong_count', 2)
      ->set('notice_attempts_available', 1)
      ->save();

    // First try:
    $this->invalidPwLogin($this->normalUser);
    // $session->pageTextContains('You have used 1 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Second try:
    $this->invalidPwLogin($this->normalUser);
    // $session->pageTextContains('You have used 2 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Third try:
    $this->invalidPwLogin($this->normalUser);
    $session->pageTextContains('The username ' . $this->normalUser->getAccountName() . ' has not been activated or is blocked');
    $this->onLoginScreen();


    // Try a normal login with the same account, as this shouldn't be possible
    // anymore:
    $this->validLogin($this->normalUser);
    $session->pageTextContains('The username ' . $this->normalUser->getAccountName() . ' has not been activated or is blocked');
    $this->onLoginScreen();

    // Try a login with a different account, as this should still be possible:
    $this->validLogin($this->secondUser);
    // Check if we have successfully logged in:
    $this->drupalGet('user');
    $session->pageTextContains($this->secondUser->getAccountName());
    $session->pageTextNotContains('Log in');
    $this->drupalGet('node/1');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123');

    // Try browsing site contents as an anonymous user:
    $this->drupalLogout();
    $this->drupalGet('node/1');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123');
  }

  // @todo This doesn't work, as "$password_message" used in the module file has
  // changed. See
  // https://www.drupal.org/project/login_security/issues/3292974.
  // /**
  //  * Test disable core login error toggle.
  //  */
  // public function testDrupalErrorToggle() {
  //   $config = \Drupal::configFactory()->getEditable('login_security.settings');

  //   $config->set('disable_core_login_error', 0)->save();

  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->responseContains($this->getDefaultDrupalLoginErrorMessage());

  //   // Block user.
  //   $this->normalUser->status->setValue(0);
  //   $this->normalUser->save();
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->responseContains($this->getDefaultDrupalBlockedUserErrorMessage($this->normalUser->getAccountName()));

  //   $config->set('disable_core_login_error', 1)->save();

  //   // Unblock user.
  //   $this->normalUser->status->setValue(1);
  //   $this->normalUser->save();
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->responseNotContains($this->getDefaultDrupalLoginErrorMessage());

  //   // Block user.
  //   $this->normalUser->status->setValue(0);
  //   $this->normalUser->save();
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->responseNotContains($this->getDefaultDrupalBlockedUserErrorMessage($this->normalUser->getAccountName()));
  // }
  // @codingStandardsIgnoreEnd

  /**
   * Test to see if a valid login won't get tracked.
   *
   * Test to see if a valid login won't get tracked by the
   * "login_security_track" table.
   */
  public function testValidLoginNotTracked() {
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Allow an abritary amount of logins:
    $config->set('user_wrong_count', 4)->save();

    // Login:
    $this->validLogin($this->normalUser);

    // The "tracking_current_count" should be 0:
    $currentVariables = _login_security_get_variables_by_name();
    $this->assertEquals(0, $currentVariables['@tracking_current_count']);
  }

  /**
   * Tests the suspicious activity no longer detected message.
   */
  public function testSupspiciousActivityNoLongerDetected() {
    $activity_threshold = 5;
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('activity_threshold', $activity_threshold)
      ->save();

    // Attempt 6 bad logins:
    for ($i = 0; $i < 6; $i++) {
      $login = [
        'name' => $this->badUsers[0]->getAccountName(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalGet('user');
      $this->submitForm($login, $this->t('Log in'));
    }

    // Ensure a log message was sent:
    $logs = $this->getLogMessages();
    $this->assertEquals(1, count($logs), '1 event was logged.');
    $log = array_pop($logs);

    // Check if the ongoing attack message appears:
    $this->assertEquals(
      'Ongoing attack detected: Suspicious activity detected in login form submissions. Too many invalid login attempts threshold reached: Currently 6 events are tracked, and threshold is configured for 5 attempts.',
      new FormattableMarkup($log->message, unserialize($log->variables)));

    // Now reset the "login_security_track" table:
    _login_security_remove_all_events(\Drupal::time()->getRequestTime() + 3600);

    // Login one more time:
    $login = [
      'name' => $this->badUsers[0]->getAccountName(),
      'pass' => 'bad_password_',
    ];
    $this->drupalGet('user');
    $this->submitForm($login, $this->t('Log in'));

    // Should be 2 logs in total now:
    $logs = $this->getLogMessages();
    $this->assertEquals(2, count($logs), '2 events were logged.');
    // We only need the new log message:
    $log = array_pop($logs);

    // Ensure it is the "suspicious activity no longer detected" message:
    $this->assertEquals(
      'Suspicious activity in login form submissions is no longer detected: Currently 1 events are being tracked, and threshold is configured for 5 maximum allowed attempts.',
      new FormattableMarkup($log->message, unserialize($log->variables)));

    // Login one more time, to see if no more logs are created:
    $login = [
      'name' => $this->badUsers[0]->getAccountName(),
      'pass' => 'bad_password_',
    ];
    $this->drupalGet('user');
    $this->submitForm($login, $this->t('Log in'));

    // Should be stil 2 logs in total:
    $logs = $this->getLogMessages();
    $this->assertEquals(2, count($logs), '2 events were logged.');

    // Attempt 5 further bad logins:
    for ($i = 0; $i < 5; $i++) {
      $login = [
        'name' => $this->badUsers[0]->getAccountName(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalGet('user');
      $this->submitForm($login, $this->t('Log in'));
    }

    // Ensure 3 log messages are set:
    $logs = $this->getLogMessages();
    $this->assertEquals(3, count($logs), '3 events were logged.');
    $log = array_pop($logs);

    // Check if the ongoing attack message reappears:
    $this->assertEquals(
      'Ongoing attack detected: Suspicious activity detected in login form submissions. Too many invalid login attempts threshold reached: Currently 6 events are tracked, and threshold is configured for 5 attempts.',
      new FormattableMarkup($log->message, unserialize($log->variables)));
  }

}
