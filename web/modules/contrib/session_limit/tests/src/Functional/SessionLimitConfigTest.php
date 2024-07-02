<?php

namespace Drupal\Tests\session_limit\Functional;

/**
 * Tests session limit module functionality.
 *
 * @group session_limit
 */
class SessionLimitConfigTest extends SessionLimitTestBase {

  /**
   * Tests must specify which theme they are using.
   *
   * @var string
   *
   * @see https://www.drupal.org/node/3083055
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests configuration form.
   */
  public function testConfigurationForm(): void {
    // Ensures that the page is accessible only to users with the adequate
    // permissions.
    $this->drupalGet('admin/config/people/session-limit');
    $this->assertSession()->statusCodeEquals(403);

    // Ensures that the config page is accessible for users with the adequate
    // permissions and the Settings form content shown.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('admin/config/people/session-limit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->elementExists('xpath', '//fieldset[@id="edit-session-limit-roles"]');
    $this->assertSession()->pageTextContains('Session limit settings');

    // Ensures that the configuration save works as expected.
    $edit = [
      'session_limit_logged_out_message_severity' => 'error',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved.');

    $expected_severity = 'error';
    $config_severity = \Drupal::config('session_limit.settings')
      ->get('session_limit_logged_out_message_severity');

    $this->assertEquals($expected_severity, $config_severity);
  }

}
