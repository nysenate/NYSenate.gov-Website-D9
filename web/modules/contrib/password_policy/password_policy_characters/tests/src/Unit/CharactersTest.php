<?php

namespace Drupal\Tests\password_policy_characters\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the characters constraint.
 *
 * @group password_policy_characters
 */
class CharactersTest extends UnitTestCase {

  /**
   * Tests the four character types and minimum character count per type.
   *
   * @dataProvider charactersDataProvider
   */
  public function testCharacters($count, $type, $password, $result) {
    $characters = $this->getMockBuilder('Drupal\password_policy_characters\Plugin\PasswordConstraint\PasswordCharacter')
      ->disableOriginalConstructor()
      ->onlyMethods(['getConfiguration', 'formatPlural'])
      ->getMock();
    $characters
      ->method('getConfiguration')
      ->willReturn(['character_count' => $count, 'character_type' => $type]);
    $user = $this->getMockBuilder('Drupal\user\Entity\User')->disableOriginalConstructor()->getMock();
    $this->assertEquals($characters->validate($password, $user)->isValid(), $result);
  }

  /**
   * Provides data for the testCharacters method.
   */
  public function charactersDataProvider() {
    return [
      // Passing conditions.
      [
        0,
        'special',
        'Password',
        TRUE,
      ],
      [
        1,
        'special',
        'Password!',
        TRUE,
      ],
      [
        2,
        'uppercase',
        'PassworD',
        TRUE,
      ],
      [
        3,
        'lowercase',
        'pasSWORD',
        TRUE,
      ],
      [
        4,
        'numeric',
        'Password1234',
        TRUE,
      ],
      [
        4,
        'letter',
        'password',
        TRUE,
      ],
      // Failing conditions.
      [
        1,
        'special',
        'Password',
        FALSE,
      ],
      [
        2,
        'uppercase',
        'Password',
        FALSE,
      ],
      [
        3,
        'lowercase',
        'paSSWORD',
        FALSE,
      ],
      [
        4,
        'numeric',
        'Password123',
        FALSE,
      ],
      [
        4,
        'letter',
        'pass',
        FALSE,
      ],
    ];
  }

}
