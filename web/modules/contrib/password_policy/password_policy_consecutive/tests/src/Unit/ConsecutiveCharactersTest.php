<?php

namespace Drupal\Tests\password_policy_consecutive\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the consecutive constraint.
 *
 * @group password_policy_consecutive
 */
class ConsecutiveCharactersTest extends UnitTestCase {

  /**
   * Tests the four consecutive character constraint.
   *
   * @dataProvider passwordsDataProvider
   */
  public function testConsecutiveCharacters($count, $password, $result) {
    $characters = $this->getMockBuilder('Drupal\password_policy_consecutive\Plugin\PasswordConstraint\ConsecutiveCharacters')
      ->disableOriginalConstructor()
      ->onlyMethods(['getConfiguration', 't'])
      ->getMock();
    $characters
      ->method('getConfiguration')
      ->willReturn(['max_consecutive_characters' => $count]);
    $user = $this->getMockBuilder('Drupal\user\Entity\User')->disableOriginalConstructor()->getMock();
    $this->assertEquals($characters->validate($password, $user)->isValid(), $result);
  }

  /**
   * Provides data for the testConsecutiveCharacters method.
   */
  public function passwordsDataProvider() {
    return [
      // Passing conditions.
      [
        2,
        'PasSword',
        TRUE,
      ],
      [
        3,
        'Password',
        TRUE,
      ],
      [
        4,
        'PasSword',
        TRUE,
      ],
      [
        5,
        'PasSsSworD',
        TRUE,
      ],
      // Failing conditions.
      [
        2,
        'Password',
        FALSE,
      ],
      [
        3,
        'Passsword',
        FALSE,
      ],
      [
        4,
        'paSSWOOOORD',
        FALSE,
      ],
      [
        5,
        'Password1233333',
        FALSE,
      ],
    ];
  }

}
