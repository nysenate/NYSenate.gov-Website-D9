<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for custom providers.
 *
 * @group oembed_providers
 */
class CustomProvidersUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test non-administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'oembed_providers',
    'oembed_providers_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer oembed providers',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);
  }

  /**
   * Tests route permissions.
   */
  public function testRoutePermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    // Non-admin user is unable to access Custom Providers listing page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers');
    $assert_session->statusCodeEquals(403);
    // Non-admin user is unable to access Custom Providers add page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/add');
    $assert_session->statusCodeEquals(403);
    // Non-admin user is unable to access Custom Providers edit page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/edit');
    $assert_session->statusCodeEquals(403);
    // Non-admin user is unable to access Custom Providers delete page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/delete');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    // Admin user is able to access Custom Providers listing page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers');
    $assert_session->statusCodeEquals(200);
    // Admin user is able to access Custom Providers add page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/add');
    $assert_session->statusCodeEquals(200);
    // Admin user is able to access Custom Providers edit page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/edit');
    $assert_session->statusCodeEquals(200);
    // Admin user is able to access Custom Providers delete page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/delete');
    $assert_session->statusCodeEquals(200);
  }

}
