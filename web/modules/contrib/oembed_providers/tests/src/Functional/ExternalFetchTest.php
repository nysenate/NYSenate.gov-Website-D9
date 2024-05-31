<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the external fetch functionality.
 *
 * @group oembed_providers
 */
class ExternalFetchTest extends BrowserTestBase {

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
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
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
        'administer blocks',
        'administer oembed providers',
      ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests external fetch enabling and disabling.
   */
  public function testExternalFetch() {
    $this->useFixtureProviders();
    $this->lockHttpClientToFixtures();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxChecked('Enable external fetch of providers');
    $page->findField('external_fetch')->uncheck();

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertFalse(\Drupal::config('oembed_providers.settings')->get('external_fetch'));

    // Verify externally-defined providers are not returned.
    $providers = \Drupal::service('media.oembed.provider_repository')->getAll();
    $provider_names = [];
    foreach ($providers as $provider) {
      $provider_names[] = $provider->getName();
    }
    $this->assertTrue(in_array('UNL MediaHub', $provider_names), "UNL MediaHub is found as a provider");
    $this->assertTrue(in_array('Example Provider', $provider_names), "Example Provider is found as a provider");
    $this->assertFalse(in_array('Vimeo', $provider_names), "Vimeo is not found as a provider");
    $this->assertFalse(in_array('YouTube', $provider_names), "YouTube is not found as a provider");
  }

}
