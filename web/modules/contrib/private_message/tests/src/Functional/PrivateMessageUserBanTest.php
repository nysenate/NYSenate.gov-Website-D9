<?php

namespace Drupal\Tests\private_message\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Private Message bans.
 *
 * @group private_message
 */
class PrivateMessageUserBanTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */

  protected $defaultTheme = 'stark';
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['private_message'];

  /**
   * The first User used for the test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $userA;

  /**
   * The second User used for the test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $userB;

  /**
   * SetUp the test class.
   */
  public function setUp(): void {
    parent::setUp();
    $this->userA = $this->drupalCreateUser([
      'use private messaging system',
      'access user profiles',
    ]);
    $this->userB = $this->drupalCreateUser([
      'use private messaging system',
      'access user profiles',
    ]);
  }

  /**
   * Tests that it's possible to block and unblock the user.
   */
  public function testBlockingUser() {
    $this->drupalLogin($this->userA);
    $userBPageUrl = $this->userB->toUrl('canonical')->toString();

    // Block the user.
    $this->drupalGet($userBPageUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Unblock');
    $this->clickLink('Block');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertEquals(1, $this->retrieveBansCount());

    // Then unblock.
    $this->drupalGet($userBPageUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Block');
    $this->clickLink('Unblock');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertEquals(0, $this->retrieveBansCount());

    // Check that it's possible to block user again.
    $this->drupalGet($userBPageUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Unblock');
    $this->assertSession()->linkExists('Block');
  }

  /**
   * Returns a count of bans.
   */
  protected function retrieveBansCount(): int {
    return $this
      ->container
      ->get('entity_type.manager')
      ->getStorage('private_message_ban')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

}
