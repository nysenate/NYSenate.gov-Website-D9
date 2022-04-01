<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test status messages functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsStatusMessagesTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'google_analytics_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if status messages tracking is properly added to the page.
   */
  public function testGoogleAnalyticsStatusMessages() {
    $ua_code = 'UA-123456-4';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Enable logging of errors only.
    $this->config('google_analytics.settings')->set('track.messages', ['error' => 'error'])->save();

    $this->drupalPostForm('user/login', [], t('Log in'));
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Error message", "Username field is required.");');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Error message", "Password field is required.");');

    // Testing this drupal_set_message() requires an extra test module.
    $this->drupalGet('google-analytics-test/drupal-messenger-add-message');
    $this->assertSession()->responseNotContains('ga("send", "event", "Messages", "Status message", "Example status message.");');
    $this->assertSession()->responseNotContains('ga("send", "event", "Messages", "Warning message", "Example warning message.");');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Error message", "Example error message.");');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Error message", "Example error message with html tags and link.");');

    // Enable logging of status, warnings and errors.
    $this->config('google_analytics.settings')->set('track.messages', [
      'status' => 'status',
      'warning' => 'warning',
      'error' => 'error',
    ])->save();

    $this->drupalGet('google-analytics-test/drupal-messenger-add-message');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Status message", "Example status message.");');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Warning message", "Example warning message.");');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Error message", "Example error message.");');
    $this->assertSession()->responseContains('ga("send", "event", "Messages", "Error message", "Example error message with html tags and link.");');
  }

}
