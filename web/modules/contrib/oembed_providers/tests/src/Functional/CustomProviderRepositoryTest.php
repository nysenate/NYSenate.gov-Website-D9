<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests custom provider loading.
 *
 * @covers \Drupal\oembed_providers\OEmbed\ProviderRepository
 *
 * @group oembed_providers
 */
class CustomProviderRepositoryTest extends MediaFunctionalTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'oembed_providers',
    'oembed_providers_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->useFixtureProviders();
    $this->lockHttpClientToFixtures();
  }

  /**
   * Tests custom provider loading.
   */
  public function testCustomProviderLoading() {
    /** @var \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator $provider_repository */
    $provider_repository = $this->container->get('media.oembed.provider_repository');

    // Verify custom providers are retrieved by getCustomProviders() method.
    $custom_providers = $provider_repository->getCustomProviders();
    $provider_names = [];
    foreach ($custom_providers as $custom_provider) {
      $provider_names[] = $custom_provider['provider_name'];
    }
    $this->assertTrue(in_array('UNL MediaHub', $provider_names), "UNL MediaHub is found as a custom provider");
    $this->assertTrue(in_array('Example Provider', $provider_names), "Example Provider is found as a custom provider");
    $this->assertFalse(in_array('Vimeo', $provider_names), "Vimeo is not found as a custom provider");

    // Verify custom providers are retrieved by getAll() method.
    $providers = $provider_repository->getAll();
    $provider_names = [];
    foreach ($providers as $provider) {
      $provider_names[] = $provider->getName();
    }
    $this->assertTrue(in_array('UNL MediaHub', $provider_names), "UNL MediaHub is found as a provider");
    $this->assertTrue(in_array('Example Provider', $provider_names), "Example Provider is found as a provider");
    $this->assertTrue(in_array('Vimeo', $provider_names), "Vimeo is found as a provider");

    // Verify custom providers are retrieved by get() method.
    $this->assertNotEmpty($provider_repository->get('UNL MediaHub'), "UNL MediaHub is found as a provider");
    $this->assertNotEmpty($provider_repository->get('Example Provider'), "Example Provider is found as a provider");
    $this->assertNotEmpty($provider_repository->get('Vimeo'), "Vimeo is found as a provider");
  }

}
