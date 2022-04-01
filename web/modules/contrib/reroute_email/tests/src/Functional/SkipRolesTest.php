<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test Reroute Email's with an allow-listed permission.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class SkipRolesTest extends RerouteEmailBrowserTestBase {

  /**
   * Basic tests for the allowlisted addresses by the permissin.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSkipRoles(): void {

    // Create a role.
    $role = $this->drupalCreateRole([]);

    // Configure to skip rerouting by a role.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
      REROUTE_EMAIL_ROLES => [$role],
    ]);

    // Create a user.
    $account = $this->drupalCreateUser();
    $account->save();

    // Submit a test email (should be rerouted).
    $this->assertMailReroutedFromTestForm(['to' => $account->getEmail()]);

    // Add a role to already existed user.
    $account->addRole($role);
    $account->save();

    // Submit a test email (should not be rerouted).
    $this->assertMailNotReroutedFromTestForm(['to' => $account->getEmail()]);
    $this->assertMailHeader('X-Rerouted-Reason', 'ROLE');
  }

}
