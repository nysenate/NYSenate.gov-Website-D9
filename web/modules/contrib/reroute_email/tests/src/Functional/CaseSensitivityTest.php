<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\reroute_email\Constants\RerouteEmailConstants;

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
      RerouteEmailConstants::REROUTE_EMAIL_ENABLE => TRUE,
      RerouteEmailConstants::REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
      RerouteEmailConstants::REROUTE_EMAIL_ALLOWLIST => $email_allowed_form,
    ]);

    // Make sure configured email was set properly.
    $email_allowed_saved = "email@allowed.com";
    $this->assertEquals($this->rerouteConfig->get(RerouteEmailConstants::REROUTE_EMAIL_ALLOWLIST), $email_allowed_saved, 'Reroute email addresses was properly set.');

    // Submit a test email (should be rerouted).
    $email_reverse_case = "eMaIl@aLlOwEd.cOm";
    $this->assertMailNotReroutedFromTestForm(['to' => $email_reverse_case]);
  }

}
