<?php

namespace Drupal\oembed_providers\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for config events.
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'configSave',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function configSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    // Keep track of whether or not this config object has already been
    // processed during the current request. This prevents an infinite loop
    // that would otherwise result when calling $config->save() from a
    // ConfigCrudEvent event. In addition to using the method name as the key
    // for drupal_static(), also use the config name to cover a scenario where
    // more than one config object is saved during the current request.
    $config_processed = &drupal_static(__METHOD__ . ':' . $config->getName());

    // Only process media type config entities.
    if (substr($config->getName(), 0, 11) !== "media.type.") {
      return;
    }

    // Skip if the media config entity has already been processed.
    if (isset($config_processed)) {
      return;
    }

    $data = $config->getRawData();
    $source = $data['source'];
    $plugin_parts = explode(':', $source);
    if ($plugin_parts[0] === "oembed") {
      // Remove dependencies from 'oembed:video' media source. This media source
      // is provided by core Media and can be overridden with the oEmbed
      // Providers module. When an override is removed, the dependnecy chain
      // would result in the media type being deleted, as well.
      if ($source == 'oembed:video') {
        $this->removeModuleDependency($data);
        $this->removeProviderBucketDependency($data);

        $config->setData($data);
        $config_processed = TRUE;
        $config->save();
      }
      // Remove 'oembed_providers' module dependency for oEmbed plugins that
      // are not provided by a Provider Bucket.
      elseif (is_null($this->entityTypeManager->getStorage('oembed_provider_bucket')->load($plugin_parts[1]))) {
        $this->removeModuleDependency($data);

        $config->setData($data);
        $config_processed = TRUE;
        $config->save();
      }
    }
  }

  /**
   * Removes the 'oembed_providers' module dependency.
   *
   * @param array $data
   *   Media type configuration data.
   */
  protected function removeModuleDependency(array &$data): void {
    // Remove the oembed_providers module dependency.
    if (isset($data['dependencies']['module'])
      && ($key = array_search('oembed_providers', $data['dependencies']['module'])) !== FALSE
      ) {

      unset($data['dependencies']['module'][$key]);
      if (empty($data['dependencies']['module'])) {
        unset($data['dependencies']['module']);
      }
    }
  }

  /**
   * Removes the provider bucket config dependency.
   *
   * @param array $data
   *   Media type configuration data.
   */
  protected function removeProviderBucketDependency(array &$data): void {
    if (isset($data['dependencies']['config'])) {
      foreach ($data['dependencies']['config'] as $key => $config) {
        if (str_starts_with($config, 'oembed_providers.bucket.')) {
          unset($data['dependencies']['config'][$key]);
          if (empty($data['dependencies']['config'])) {
            unset($data['dependencies']['config']);
          }
        }
      }
    }
  }

}
