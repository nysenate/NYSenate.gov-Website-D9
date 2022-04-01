<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test Reroute Email with mail keys filter.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class MailKeysTest extends RerouteEmailBrowserTestBase {

  /**
   * Test Reroute Email with mail keys filter.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testMailKeysSettings(): void {
    // Configure to reroute all outgoing emails.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
    ]);
    $this->assertMailReroutedFromTestForm(['to' => $this->originalDestination]);

    // Configure to NOT reroute all outgoing emails (not existed mail key).
    $this->configureRerouteEmail([REROUTE_EMAIL_MAILKEYS => 'not_existed_module']);
    $this->assertMailNotReroutedFromTestForm(['to' => $this->originalDestination]);
    $this->assertMailHeader('X-Rerouted-Reason', 'MAILKEY-ALLOWED');

    // Configure to reroute emails from our test form.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_MAILKEYS => 'reroute_email_test_email_form',
    ]);
    $this->assertMailReroutedFromTestForm(['to' => $this->originalDestination]);

    // Configure to reroute all outgoing emails (not existed mail key).
    $this->configureRerouteEmail([
      REROUTE_EMAIL_MAILKEYS => '',
      REROUTE_EMAIL_MAILKEYS_SKIP => 'not_existed_module',
    ]);
    $this->assertMailReroutedFromTestForm(['to' => $this->originalDestination]);

    // Configure to NOT reroute outgoing emails from our test form.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_MAILKEYS_SKIP => 'reroute_email_test_email_form',
    ]);
    $this->assertMailNotReroutedFromTestForm(['to' => $this->originalDestination]);
    $this->assertMailHeader('X-Rerouted-Reason', 'MAILKEY-SKIPPED');
  }

}
