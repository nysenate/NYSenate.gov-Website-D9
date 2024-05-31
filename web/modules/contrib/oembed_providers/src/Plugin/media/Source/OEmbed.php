<?php

namespace Drupal\oembed_providers\Plugin\media\Source;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\media\Plugin\media\Source\OEmbed as CoreOEmbed;

/**
 * Provides a media source plugin for oEmbed resources.
 *
 * This class is a plugin that replaces
 * \Drupal\media\Plugin\media\Source\OEmbed. It has an identical id to the core
 * plugin it replaces. (Due to module weights, it overwrites the core plugin.)
 * The sole purpose of this class is to extend core OEmbed and add a
 * ::calculateDependencies method.
 *
 * @MediaSource(
 *   id = "oembed",
 *   label = @Translation("oEmbed source"),
 *   description = @Translation("Use oEmbed URL for reusable media."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   deriver = "Drupal\media\Plugin\media\Source\OEmbedDeriver",
 *   providers = {},
 * )
 */
class OEmbed extends CoreOEmbed {

  use DependencyTrait;

  /**
   * {@inheritdoc}
   *
   * Note that oembed_providers will be added as a module dependency for all
   * oEmbed media sources, even those provided by core or other contrib
   * modules. This is unavoidable due to
   * Drupal\Core\Plugin\PluginDependencyTrait::calculatePluginDependencies().
   * To address this, we remove use
   * \Drupal\oembed_providers\EventSubscriber\ConfigEventsSubscriber to remove
   * the undesired module dependency when config is saved.
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add a dependency for the provider bucket that is responsible for
    // this media source.
    /** @var \Drupal\oembed_providers\Entity\ProviderBucket */
    $provider_bucket = $this->entityTypeManager->getStorage('oembed_provider_bucket')->load($this->pluginDefinition['id']);
    // Not all oEmbed media sources are created by the oEmbed Providers
    // module. Check that a provider bucket is loaded.
    if ($provider_bucket) {
      $this->addDependency('config', $provider_bucket->getConfigDependencyName());
    }

    return $this->dependencies;
  }

}
