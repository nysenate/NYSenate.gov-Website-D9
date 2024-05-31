<?php

declare(strict_types=1);

namespace Drupal\Tests\email_registration\Functional;

use Drupal\Tests\user\Functional\UserLoginHttpTest as CoreUserLoginHttpTest;

/**
 * Tests login and password reset via direct HTTP.
 *
 * @group user
 */
class UserLoginHttpTest extends CoreUserLoginHttpTest {

  /**
   * Tests user login with username and with email.
   */
  protected function testLoginWithUsernameAndEmail(): void {
    $format = 'json';

    // Create account and extract the data.
    $account = $this->drupalCreateUser();
    $name = $account->getAccountName();
    $mail = $account->getEmail();
    $pass = $account->passRaw;

    // Login with username.
    $response = $this->loginRequest($name, $pass, $format);
    $this->assertEquals(200, $response->getStatusCode());
    $result_data = $this->serializer->decode($response->getBody(), $format);
    $logout_token = $result_data['logout_token'];

    // Log out.
    $response = $this->logoutRequest($format, $logout_token);
    $this->assertEquals(204, $response->getStatusCode());

    // Log in with email.
    $response = $this->loginRequest($mail, $pass, $format);
    $this->assertEquals(200, $response->getStatusCode());
  }

}
