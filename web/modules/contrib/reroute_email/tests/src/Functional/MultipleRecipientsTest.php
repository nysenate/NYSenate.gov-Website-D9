<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test Reroute Email with multiple recipients.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class MultipleRecipientsTest extends RerouteEmailBrowserTestBase {

  /**
   * Test Reroute Email with multiple recipients.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testMultipleRecipients(): void {
    // Set multiple rerouting emails and a domain to the allowed list.
    // Multiple commas and semicolons are added for validation tests.
    $emails_reroute_to_form = "user1@reroute-to.com, \nuser2@reroute-to.com,;;, ,\nuser3@reroute-to.com\n";
    $emails_reroute_to_result = "user1@reroute-to.com,user2@reroute-to.com,user3@reroute-to.com";
    $email_allow_domain = '*@allowlisted.com';
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ADDRESS => $emails_reroute_to_form,
      REROUTE_EMAIL_ALLOWLIST => $email_allow_domain,
    ]);

    // Make sure configured emails were set properly.
    $this->assertEquals($this->rerouteConfig->get(REROUTE_EMAIL_ADDRESS), $emails_reroute_to_result, 'Reroute email addresses was set.');
    $this->assertEquals($this->rerouteConfig->get(REROUTE_EMAIL_ALLOWLIST), $email_allow_domain, 'Value was set to the allowed list.');

    // Submit a test email (should be rerouted).
    $this->assertMailReroutedFromTestForm(['to' => 'email@not-allowlisted.com, email@allowlisted.com']);

    // Submit a test email (should not be rerouted).
    $this->assertMailNotReroutedFromTestForm(['to' => 'user1@allowlisted.com, name2@allowlisted.com, allowed3@allowlisted.com']);
  }

}
