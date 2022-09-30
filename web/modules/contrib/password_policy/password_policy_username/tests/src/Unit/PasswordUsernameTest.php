<?php

namespace Drupal\Tests\password_policy_username\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Tests the password username constraint.
 *
 * @group password_policy_username
 */
class PasswordUsernameTest extends UnitTestCase {

  /**
   * Tests the password to make sure it doesn't contain the user's username.
   *
   * @dataProvider passwordUsernameDataProvider
   */
  public function testPasswordUsername($disallow_username, UserInterface $user, $password, $result) {
    $username_test = $this->getMockBuilder('Drupal\password_policy_username\Plugin\PasswordConstraint\PasswordUsername')
      ->disableOriginalConstructor()
      ->onlyMethods(['getConfiguration', 't'])
      ->getMock();

    $username_test
      ->method('getConfiguration')
      ->willReturn(['disallow_username' => $disallow_username]);

    $this->assertEquals($username_test->validate($password, $user)->isValid(), $result);
  }

  /**
   * Provides data for the testPasswordUsername method.
   */
  public function passwordUsernameDataProvider() {
    $user = $this->getMockBuilder('Drupal\user\Entity\User')->disableOriginalConstructor()->getMock();
    $user->method('getAccountName')->willReturn('username');

    return [
      // Passing conditions.
      [
        TRUE,
        $user,
        'password',
        TRUE,
      ],
      [
        FALSE,
        $user,
        'username',
        TRUE,
      ],
      // Failing conditions.
      [
        TRUE,
        $user,
        'username',
        FALSE,
      ],
      [
        TRUE,
        $user,
        'my_username',
        FALSE,
      ],
    ];
  }

}
