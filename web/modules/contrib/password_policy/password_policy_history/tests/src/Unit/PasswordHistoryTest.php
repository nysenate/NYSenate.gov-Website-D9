<?php

namespace Drupal\Tests\password_policy_history\FunctionalJavascript;

use Drupal\password_policy_history\Plugin\PasswordConstraint\PasswordHistory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests password history.
 *
 * @group password_policy_history
 */
class PasswordHistoryTest extends UnitTestCase {

  /**
   * The PasswordHistory mock.
   *
   * @var \Drupal\password_policy_history\Plugin\PasswordConstraint\PasswordHistory
   */
  public $passwordHistoryMock;

  /**
   * The User mock.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user;

  /**
   * Set up the test mock.
   */
  public function setup(): void {
    $password_reuse = $this->getMockBuilder(PasswordHistory::class)
      ->onlyMethods(['getHashes', 'getPasswordService', 't'])
      ->disableOriginalConstructor()
      ->getMock();

    $password_reuse
      ->expects($this->once())
      ->method('getHashes')
      ->willReturn([(object) ['pass_hash' => 'fake_password']]);
    $this->user = $this->getMockBuilder('Drupal\user\UserInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->user
      ->expects($this->once())
      ->method('id')
      ->willReturn(1);
    $this->passwordHistoryMock = $password_reuse;
  }

  /**
   * Ensure that a password check success results in the correct output.
   *
   * @dataProvider userContextProvider
   */
  public function testPasswordReuseValid($password) {
    $passwordService = $this->getPasswordService(FALSE);

    $this->passwordHistoryMock->expects($this->once())
      ->method('getPasswordService')
      ->willReturn($passwordService);

    $this->assertEquals($this->passwordHistoryMock->validate($password, $this->user)->isValid(), TRUE);
  }

  /**
   * Ensure that a password check failure results in the correct output.
   *
   * @dataProvider userContextProvider
   */
  public function testPasswordReuseInvalid($password) {
    $passwordService = $this->getPasswordService(TRUE);

    $this->passwordHistoryMock->expects($this->once())
      ->method('getPasswordService')
      ->willReturn($passwordService);

    $this->passwordHistoryMock->expects($this->once())
      ->method('t')
      ->willReturn('Invalid password');
    $this->assertEquals($this->passwordHistoryMock->validate($password, $this->user)->isValid(), FALSE);
  }

  /**
   * Return a password interface mock object.
   *
   * @return \Drupal\Core\Password\PasswordInterface
   *   The password interface mock.
   */
  public function getPasswordService($return) {
    $password_service = $this->getMockBuilder('Drupal\Core\Password\PasswordInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $password_service->method('check')->willReturn($return);

    return $password_service;
  }

  /**
   * Data provider for the user context.
   *
   * @return array
   *   The user context array.
   */
  public function userContextProvider() {
    return [['password']];
  }

}
