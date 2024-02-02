<?php

namespace Drupal\Tests\password_policy_length\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the character length constraint.
 *
 * @group password_policy_length
 */
class PasswordLengthTest extends UnitTestCase {

  /**
   * Tests the password length.
   *
   * @dataProvider lengthDataProvider
   */
  public function testLength($length, $operation, $password, $result) {
    $characters = $this->getMockBuilder('Drupal\password_policy_length\Plugin\PasswordConstraint\PasswordLength')
      ->disableOriginalConstructor()
      ->onlyMethods(['getConfiguration', 'formatPlural'])
      ->getMock();
    $characters
      ->method('getConfiguration')
      ->willReturn([
        'character_length' => $length,
        'character_operation' => $operation,
      ]);
    $user = $this->getMockBuilder('Drupal\user\Entity\User')->disableOriginalConstructor()->getMock();
    $this->assertEquals($characters->validate($password, $user)->isValid(), $result);
  }

  /**
   * Provides data for the testLength method.
   */
  public function lengthDataProvider() {
    return [
      // Passing conditions.
      [
        1,
        'minimum',
        'P',
        TRUE,
      ],
      [
        1,
        'maximum',
        'P',
        TRUE,
      ],
      [
        10,
        'minimum',
        '1234567890',
        TRUE,
      ],
      [
        10,
        'maximum',
        'Password',
        TRUE,
      ],
      // Failing conditions.
      [
        1,
        'minimum',
        '',
        FALSE,
      ],
      [
        1,
        'maximum',
        'Pa',
        FALSE,
      ],
      [
        10,
        'minimum',
        'Password',
        FALSE,
      ],
      [
        10,
        'maximum',
        'PasswordPassword',
        FALSE,
      ],
    ];
  }

}
