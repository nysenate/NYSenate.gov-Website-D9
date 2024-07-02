<?php

namespace Drupal\media_migration_test_oembed;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replaces oEmbed-related media services which would make outbound requests.
 */
class MediaMigrationTestOembedServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('media.oembed.url_resolver')->setClass(UrlResolver::class);
    $container->getDefinition('media.oembed.resource_fetcher')->setClass(ResourceFetcher::class);
  }

}
