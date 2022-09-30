<?php

namespace Drupal\entity_usage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;

/**
 * Class EntityUpdateManager.
 *
 * @package Drupal\entity_usage
 */
class EntityUpdateManager implements EntityUpdateManagerInterface {

  /**
   * The usage track service.
   *
   * @var \Drupal\entity_usage\EntityUsage
   */
  protected $usageService;

  /**
   * The usage track manager.
   *
   * @var \Drupal\entity_usage\EntityUsageTrackManager
   */
  protected $trackManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * EntityUpdateManager constructor.
   *
   * @param \Drupal\entity_usage\EntityUsage $usage_service
   *   The usage tracking service.
   * @param \Drupal\entity_usage\EntityUsageTrackManager $track_manager
   *   The PluginManager track service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityUsage $usage_service, EntityUsageTrackManager $track_manager, ConfigFactoryInterface $config_factory) {
    $this->usageService = $usage_service;
    $this->trackManager = $track_manager;
    $this->config = $config_factory->get('entity_usage.settings');

  }

  /**
   * {@inheritdoc}
   */
  public function trackUpdateOnCreation(EntityInterface $entity) {
    if (!$this->allowSourceEntityTracking($entity)) {
      return;
    }

    // Call all plugins that want to track entity usages. We need to call this
    // for all translations as well since Drupal stores new revisions for all
    // translations by default when saving an entity.
    if ($entity instanceof TranslatableInterface) {
      foreach ($entity->getTranslationLanguages() as $translation_language) {
        if ($entity->hasTranslation($translation_language->getId())) {
          /** @var \Drupal\Core\Entity\EntityInterface $translation */
          $translation = $entity->getTranslation($translation_language->getId());
          foreach ($this->getEnabledPlugins() as $plugin) {
            $plugin->trackOnEntityCreation($translation);
          }
        }
      }
    }
    else {
      // Not translatable, just call the plugins with the entity itself.
      foreach ($this->getEnabledPlugins() as $plugin) {
        $plugin->trackOnEntityCreation($entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackUpdateOnEdition(EntityInterface $entity) {
    if (!$this->allowSourceEntityTracking($entity)) {
      return;
    }

    // Call all plugins that want to track entity usages. We need to call this
    // for all translations as well since Drupal stores new revisions for all
    // translations by default when saving an entity.
    if ($entity instanceof TranslatableInterface) {
      foreach ($entity->getTranslationLanguages() as $translation_language) {
        if ($entity->hasTranslation($translation_language->getId())) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
          $translation = $entity->getTranslation($translation_language->getId());
          foreach ($this->getEnabledPlugins() as $plugin) {
            $plugin->trackOnEntityUpdate($translation);
          }
        }
      }
    }
    else {
      // Not translatable, just call the plugins with the entity itself.
      foreach ($this->getEnabledPlugins() as $plugin) {
        $plugin->trackOnEntityUpdate($entity);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function trackUpdateOnDeletion(EntityInterface $entity, $type = 'default') {
    // When an entity is being deleted the logic is much simpler and we don't
    // even need to call the plugins. Just delete the records that affect this
    // entity both as target and source.
    switch ($type) {
      case 'revision':
        $this->usageService->deleteBySourceEntity($entity->id(), $entity->getEntityTypeId(), NULL, $entity->getRevisionId());
        break;

      case 'translation':
        $this->usageService->deleteBySourceEntity($entity->id(), $entity->getEntityTypeId(), $entity->language()->getId());
        break;

      case 'default':
        $this->usageService->deleteBySourceEntity($entity->id(), $entity->getEntityTypeId());
        $this->usageService->deleteByTargetEntity($entity->id(), $entity->getEntityTypeId());
        break;

      default:
        // We only accept one of the above mentioned types.
        throw new \InvalidArgumentException('EntityUpdateManager::trackUpdateOnDeletion called with unkown deletion type: ' . $type);
    }
  }

  /**
   * Check if an entity is allowed to be tracked as source.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the entity can be tracked or not.
   */
  protected function allowSourceEntityTracking(EntityInterface $entity) {
    $allow_tracking = FALSE;
    $entity_type = $entity->getEntityType();
    $enabled_source_entity_types = $this->config->get('track_enabled_source_entity_types');
    if (!is_array($enabled_source_entity_types) && ($entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface'))) {
      // When no settings are defined, track all content entities by default.
      $allow_tracking = TRUE;
    }
    elseif (is_array($enabled_source_entity_types) && in_array($entity_type->id(), $enabled_source_entity_types, TRUE)) {
      $allow_tracking = TRUE;
    }
    return $allow_tracking;
  }

  /**
   * Get the enabled tracking plugins, all plugins are enabled by default.
   *
   * @return array<string, \Drupal\entity_usage\EntityUsageTrackInterface>
   *   The enabled plugin instances keyed by plugin ID.
   */
  protected function getEnabledPlugins() {
    $all_plugin_ids = array_keys($this->trackManager->getDefinitions());
    $enabled_plugins = $this->config->get('track_enabled_plugins');
    $enabled_plugin_ids = is_array($enabled_plugins) ? $enabled_plugins : $all_plugin_ids;

    $plugins = [];
    foreach (array_intersect($all_plugin_ids, $enabled_plugin_ids) as $plugin_id) {
      $plugins[$plugin_id] = $this->trackManager->createInstance($plugin_id);
    }

    return $plugins;
  }

}
