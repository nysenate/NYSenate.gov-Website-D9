<?php

declare(strict_types=1);

namespace Drupal\Tests\email_registration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\email_registration\Traits\EmailRegistrationTestTrait;

/**
 * This class provides methods specifically for testing something.
 *
 * @group email_registration
 */
abstract class EmailRegistrationFunctionalTestBase extends BrowserTestBase {
  use EmailRegistrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
    'email_registration',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([], 'user', FALSE, ['mail' => 'user@user.com']);
    $this->adminUser = $this->drupalCreateUser([], 'adminUser', TRUE, ['mail' => 'admin@admin.com']);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));

    $this->adminUser->save();
  }

}
