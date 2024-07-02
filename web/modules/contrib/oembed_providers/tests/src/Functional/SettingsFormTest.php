<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the global settings form.
 *
 * @group oembed_providers
 */
class SettingsFormTest extends BrowserTestBase {

  use OEmbedTestTrait;

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
    'block',
    'media',
    'oembed_providers',
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
        'administer blocks',
        'administer oembed providers',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests route permissions.
   */
  public function testRoutePermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    // Non-admin user is unable to access settings page.
    $this->drupalGet('/admin/config/media/oembed-providers');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    // Admin user is unable to access settings page.
    $this->drupalGet('/admin/config/media/oembed-providers');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests settings form.
   */
  public function testSettingsForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set dummy value in cache, so it can be deleted on form submission.
    \Drupal::cache()->set('oembed_providers:oembed_providers', 'test value', \Drupal::time()->getRequestTime() + (86400));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxChecked('Enable external fetch of providers');
    $this->assertSame('https://oembed.com/providers.json', $page->findField('oembed_providers_url')->getValue());

    $page
      ->findField('oembed_providers_url')
      ->setValue('https://example.com/providers.json');

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertSame('https://example.com/providers.json', $this->config('media.settings')->get('oembed_providers_url'));

    // Verify cached providers are cleared.
    $this->AssertFalse(\Drupal::cache()->get('oembed_providers:oembed_providers'));

    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxChecked('Enable external fetch of providers');
    $this->assertSame('https://example.com/providers.json', $page->findField('oembed_providers_url')->getValue());

    $page->findField('external_fetch')->uncheck();

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertSame(FALSE, $this->config('oembed_providers.settings')->get('external_fetch'));

    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxNotChecked('Enable external fetch of providers');

    // Verify form validation.
    $this->drupalGet('/admin/config/media/oembed-providers');

    $page->findField('external_fetch')->check();
    $page->findField('oembed_providers_url')->setValue('');

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The oEmbed Providers URL field is required.');

    // Test provider key-value clear.
    \Drupal::service('keyvalue')->get('media')->set('oembed_providers', 'test value');

    $this->drupalGet('/admin/config/media/oembed-providers');

    $page->pressButton('Clear Provider Cache');
    $this->assertNull(\Drupal::service('keyvalue')->get('media')->get('oembed_providers'));
  }

}
