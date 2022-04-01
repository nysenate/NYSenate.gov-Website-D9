<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test Reroute Email functionality for case sensitivity.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class CaseSensitivityTest extends RerouteEmailBrowserTestBase {

  /**
   * Test case-sensitive email addresses.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCaseSensitiveAllowedListEmail(): void {

    // Configure reroute_email module.
    $email_allowed_form = "EmAiL@AlLoWed.CoM";
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
      REROUTE_EMAIL_ALLOWLIST => $email_allowed_form,
    ]);

    // Make sure configured email was set properly.
    $email_allowed_saved = "email@allowed.com";
    $this->assertEquals($this->rerouteConfig->get(REROUTE_EMAIL_ALLOWLIST), $email_allowed_saved, 'Reroute email addresses was properly set.');

    // Submit a test email (should be rerouted).
    $email_reverse_case = "eMaIl@aLlOwEd.cOm";
    $this->assertMailNotReroutedFromTestForm(['to' => $email_reverse_case]);
  }

}
