<?php

declare(strict_types = 1);

namespace Drupal\geocoder\Plugin;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\geocoder\ProviderInterface;

/**
 * Provides a container for lazily loading Geocoder provider plugins.
 */
class GeocoderProviderPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\geocoder\ProviderInterface
   *   The Geocoder Provider.
   */
  public function &get($instance_id): ProviderInterface {
    return parent::get($instance_id);
  }

}
