<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\media\Functional\ProviderRepositoryTest as CoreProviderRepositoryTest;

/**
 * Run core ProviderRepositoryTest test against decorated Provider Repository.
 *
 * @covers \Drupal\oembed_providers\OEmbed\ProviderRepository
 *
 * @group oembed_providers
 */
class ProviderRepositoryTest extends CoreProviderRepositoryTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'oembed_providers',
  ];

  /**
   * Tests that hook_oembed_providers_alter() is invoked.
   */
  public function testProvidersAlterHook() {
    $this->container->get('module_installer')->install(['oembed_providers_test']);
    $providers = $this->container->get('media.oembed.provider_repository')->getAll();
    $this->assertArrayHasKey('My Custom Provider', $providers);
  }

}
