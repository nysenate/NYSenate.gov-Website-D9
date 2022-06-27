<?php

namespace Drupal\Tests\entityqueue\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for entityqueue module.
 *
 * @group entityqueue
 */
class EntityQueueUiTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['entityqueue_test'];

  /**
   * A user with the 'administer entityqueue' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['administer entityqueue']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests entity queue list page.
   */
  public function testListPage() {
    $this->drupalGet('/admin/structure/entityqueue');
    $this->assertSession()->pageTextContains('There are no disabled queues');
  }

}
