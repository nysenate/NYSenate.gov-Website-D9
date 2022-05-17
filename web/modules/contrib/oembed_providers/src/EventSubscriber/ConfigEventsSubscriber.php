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

    // Only process media type config entities.
    if (substr($config->getName(), 0, 11) !== "media.type.") {
      return;
    }

    // Skip if the media config entity has already been processed.
    if (isset($config->oembedProvidersProcessed)) {
      return;
    }

    $data = $config->getRawData();
    $plugin_parts = explode(':', $data['source']);
    // Only process oEmbed plugins that are not provided by a Provider Bucket.
    if ($plugin_parts[0] === "oembed"
      && is_null($this->entityTypeManager->getStorage('oembed_provider_bucket')->load($plugin_parts[1]))
      ) {

      // Remove the oembed_providers module dependency.
      if (isset($data['dependencies']['module'])
        && ($key = array_search('oembed_providers', $data['dependencies']['module'])) !== FALSE
        ) {

        unset($data['dependencies']['module'][$key]);
        if (empty($data['dependencies']['module'])) {
          unset($data['dependencies']['module']);
        }
      }

      $config->setData($data);
      $config->oembedProvidersProcessed = TRUE;
      $config->save();
    }
  }

}
