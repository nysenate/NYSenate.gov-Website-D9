<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Test for default address.
 *
 * When reroute email addresses field is not configured, attempt to use the site
 * email address, otherwise use sendmail_from system variable.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class DefaultAddressesTest extends RerouteEmailBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['reroute_email', 'dblog'];

  /**
   * Enable modules and create user with specific permissions.
   */
  protected function setUp(): void {
    // Add more permissions to access recent log messages in test.
    $this->permissions[] = 'access site reports';
    parent::setUp();
  }

  /**
   * Test reroute email address is set to site_mail, sendmail_from or empty.
   *
   * When reroute email addresses field is not configured and settings haven't
   * been configured yet, check if the site email address or the sendmail_from
   * system variable are properly used as fallbacks. Additionally, check that
   * emails are aborted and a watchdog entry logged if reroute email address is
   * set to an empty string.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRerouteDefaultAddress(): void {

    // Check default value for reroute_email_address when not configured.
    // If system.site's 'mail' is not empty, it should be the default value.
    $site_mail = $this->config('system.site')->get('mail');
    $this->assertTrue(isset($site_mail), new FormattableMarkup('Site mail is not empty: @site_mail.', ['@site_mail' => $site_mail]));

    // Programmatically enable email rerouting.
    $this->rerouteConfig->set(REROUTE_EMAIL_ENABLE, TRUE)->save();

    // Load Reroute Email Settings form page. Ensure rerouting is enabled.
    $this->drupalGet('admin/config/development/reroute_email');
    $this->assertSession()->checkboxChecked('edit-enable');
    $this->assertTrue($this->rerouteConfig->get(REROUTE_EMAIL_ENABLE), 'Rerouting is enabled.');

    // Email addresses field default value is system.site.mail.
    $this->assertSession()->fieldValueEquals(REROUTE_EMAIL_ADDRESS, $site_mail);

    // Ensure reroute_email_address is actually empty at this point.
    $this->assertNull($this->rerouteConfig->get(REROUTE_EMAIL_ADDRESS), 'Reroute email destination address is not configured.');

    // Submit a test email, check if it is rerouted to system.site.mail address.
    $this->drupalGet($this->rerouteTestFormPath);
    $this->submitForm(['to' => 'to@example.com'], 'Send email');
    $this->assertSession()->pageTextContains(t('Test email submitted for delivery from test form.'));
    $this->assertCount(1, $this->getMails(), 'Exactly one email captured.');

    // Check rerouted email is the site email address.
    $this->assertMail('to', $site_mail, new FormattableMarkup('Email was properly rerouted to site email address: @default_destination.', ['@default_destination' => $site_mail]));

    // Unset system.site.mail.
    $this
      ->config('system.site')
      ->set('mail', NULL)
      ->save();

    // Configure the allowed list of addresses as an empty string to abort all
    // emails.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ALLOWLIST => '',
    ]);

    // Make sure configured emails values are an empty string.
    $this->assertSame($this->rerouteConfig->get(REROUTE_EMAIL_ADDRESS), '', 'Reroute email destination address is an empty string.');
    $this->assertSame($this->rerouteConfig->get(REROUTE_EMAIL_ALLOWLIST), '', 'Allowed email address is an empty string.');

    // Flush the Test Mail collector to ensure it is empty for this tests.
    \Drupal::state()->set('system.test_mail_collector', []);

    // Submit a test email to check if it is aborted.
    $this->drupalGet($this->rerouteTestFormPath);
    $this->submitForm(['to' => 'to@example.com'], t('Send email'));
    $this->assertCount(0, $this->getMails(), 'Email sending was properly aborted because rerouting email address is an empty string.');

    // Check status message is displayed properly after email form submission.
    $this->assertSession()->pageTextContains($this->t('An email (ID: @message_id) either aborted or rerouted to the configured address.', ['@message_id' => 'reroute_email_test_email_form']));

    // Check the watchdog entry logged with aborted email message.
    $this->drupalGet('admin/reports/dblog');

    // Check the link to the watchdog detailed message.
    $dblog_link = $this->xpath('//table[@id="admin-dblog"]/tbody/tr[contains(@class,"dblog-reroute-email")][1]/td[text()="reroute_email"]/following-sibling::td/a[contains(text(),"reroute_email")]');
    $link_label = $dblog_link[0]->getText();
    $this->assertTrue(isset($dblog_link[0]), new FormattableMarkup('Logged a message in dblog: <em>@link</em>.', ['@link' => $link_label]));

    // Open the full view page of the log message found for reroute_email.
    $this->clickLink($link_label);

    // Ensure the correct email is logged with default 'to' placeholder.
    $this->assertSession()->pageTextContains($this->t('An email (ID: @message_id) was either rerouted or aborted.Detailed email data: Array $message', ['@message_id' => 'reroute_email_test_email_form']));
  }

}
