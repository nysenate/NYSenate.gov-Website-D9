<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Tests\BrowserTestBase;

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
  public static $modules = ['login_security'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
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
   * Alternative to drupalLogin().
   */
  protected function drupalLoginLite($user) {
    if ($this->drupalUserIsLoggedIn($user)) {
      $this->drupalLogout();
    }

    $edit = [
      'name' => $user->getAccountName(),
      'pass' => $user->getPassword(),
    ];

    $this->drupalPostForm('user', $edit, $this->t('Log in'));
    $this->assertResponse(200, $this->t('Login page reloaded.'));

    $this->loggedInUser = TRUE;
  }

}
