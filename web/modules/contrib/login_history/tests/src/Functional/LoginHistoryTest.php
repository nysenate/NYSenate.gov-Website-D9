<?php

namespace Drupal\Tests\login_history\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Login History functionality.
 *
 * @group Other
 */
class LoginHistoryTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  static public $modules = ['block', 'login_history'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * The admin user that will be created.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The authenticated user that will be created.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access site reports',
      'access administration pages',
      'administer blocks',
      'view all login histories',
      'view own login history',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('last_login_block');
    $this->drupalLogout();

    $this->authenticatedUser = $this->drupalCreateUser([]);
  }

  /**
   * Test Login History.
   */
  public function testLoginHistory() {
    // Verify we can successfully access the Login history page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/login-history');
    $this->assertResponse(200);

    // Verify the Login history page has the table.
    $this->assertText('Date');
    $this->assertText('Username');
    $this->assertText('IP Address');
    $this->assertText('One-time login?');
    $this->assertText('User Agent');

    // Verify the Last Login block is on the home page.
    $this->drupalGet('<front>');
    $this->assertText('You last logged in from');

    // Verify the link is in the block.
    $this->clickLink($this->t('View your login history'));

    // Verify the Login History tab is reachable.
    $this->drupalGet('user/' . $this->adminUser->id() . '/login-history');
    $this->assertResponse(200);

    $this->drupalLogout();

    // Verify the Authenticated User
    // cannot go to the admin page,
    // doesn't see the block,
    // doesn't have the Login History tab in their user profile.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('admin/reports/login-history');
    $this->assertSession()->statusCodeNotEquals(200);

    $this->drupalGet('<front>');
    $this->assertNoText('You last logged in from');

    $this->drupalGet('user/' . $this->authenticatedUser->id() . '/login-history');
    $this->assertResponse(403);

    $this->drupalLogout();
  }

}
