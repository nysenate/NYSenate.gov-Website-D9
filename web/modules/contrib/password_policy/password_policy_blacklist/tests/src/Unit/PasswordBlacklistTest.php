<?php

namespace Drupal\Tests\password_policy_blacklist\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the password blacklist constraint.
 *
 * @group password_policy_blacklist
 */
class PasswordBlacklistTest extends UnitTestCase {

  /**
   * Tests the password blacklist.
   *
   * @dataProvider passwordBlacklistDataProvider
   */
  public function testPasswordBlacklist($blacklist, $match_substrings, $password, $result) {
    $blacklist_test = $this->getMockBuilder('Drupal\password_policy_blacklist\Plugin\PasswordConstraint\PasswordBlacklist')
      ->disableOriginalConstructor()
      ->onlyMethods(['getConfiguration', 't'])
      ->getMock();

    $blacklist_test
      ->method('getConfiguration')
      ->willReturn([
        'blacklist' => $blacklist,
        'match_substrings' => $match_substrings,
      ]);

    $this->assertEquals($blacklist_test->validate($password, NULL)->isValid(), $result);
  }

  /**
   * Provides data for the testPasswordBlacklist method.
   */
  public function passwordBlacklistDataProvider() {
    return [
      // Passing conditions.
      [
        ['password'],
        FALSE,
        'testpass',
        TRUE,
      ],
      [
        ['password'],
        TRUE,
        'testpass',
        TRUE,
      ],
      // Failing conditions.
      [
        ['password'],
        FALSE,
        'password',
        FALSE,
      ],
      [
        ['password'],
        TRUE,
        'testpassword',
        FALSE,
      ],
      // Unusual inputs.
      [
        [''],
        FALSE,
        'password',
        TRUE,
      ],
      [
        [''],
        TRUE,
        'password',
        TRUE,
      ],
      [
        [''],
        FALSE,
        '',
        TRUE,
      ],
    ];
  }

}
