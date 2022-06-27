<?php

namespace Drupal\entityqueue\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\entityqueue\EntityQueueHandlerPluginCollection;
use Drupal\entityqueue\EntityQueueInterface;

/**
 * Defines the EntityQueue entity class.
 *
 * @ConfigEntityType(
 *   id = "entity_queue",
 *   label = @Translation("Entity queue"),
 *   handlers = {
 *     "list_builder" = "Drupal\entityqueue\EntityQueueListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entityqueue\Form\EntityQueueForm",
 *       "edit" = "Drupal\entityqueue\Form\EntityQueueForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "access" = "Drupal\entityqueue\EntityQueueAccessControlHandler",
 *   },
 *   admin_permission = "administer entityqueue",
 *   config_prefix = "entity_queue",
 *   bundle_of = "entity_subqueue",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/entityqueue/{entity_queue}",
 *     "delete-form" = "/admin/structure/entityqueue/{entity_queue}/delete",
 *     "collection" = "/admin/structure/entityqueue",
 *     "enable" = "/admin/structure/entityqueue/{entity_queue}/enable",
 *     "disable" = "/admin/structure/entityqueue/{entity_queue}/disable",
 *     "subqueue-list" = "/admin/structure/entityqueue/{entity_queue}/list"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "handler",
 *     "handler_configuration",
 *     "entity_settings",
 *     "queue_settings"
 *   }
 * )
 */
class EntityQueue extends ConfigEntityBundleBase implements EntityQueueInterface, EntityWithPluginCollectionInterface {

  /**
   * The EntityQueue ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The EntityQueue label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity selection settings used for the subqueue's 'items' field.
   *
   * @var array
   */
  protected $entity_settings = [
    'target_type' => 'node',
    'handler' => 'default',
    'handler_settings' => [],
  ];

  /**
   * The queue settings.
   *
   * @var array
   */
  protected $queue_settings = [
    'min_size' => 0,
    'max_size' => 0,
    'act_as_queue' => FALSE,
    'reverse' => FALSE,
  ];

  /**
   * The ID of the EntityQueueHandler.
   *
   * @var string
   */
  protected $handler = 'simple';

  /**
   * An array to store and load the EntityQueueHandler plugin configuration.
   *
   * @var array
   */
  protected $handler_configuration = [];

  /**
   * The EntityQueueHandler plugin.
   *
   * @var \Drupal\entityqueue\EntityQueueHandlerPluginCollection
   */
  protected $handlerPluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->entity_settings['target_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMinimumSize() {
    return $this->queue_settings['min_size'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMaximumSize() {
    return $this->queue_settings['max_size'];
  }

  /**
   * {@inheritdoc}
   */
  public function getActAsQueue() {
    return $this->queue_settings['act_as_queue'];
  }

  /**
   * {@inheritdoc}
   */
  public function isReversed() {
    return isset($this->queue_settings['reverse']) ? $this->queue_settings['reverse'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitySettings() {
    return $this->entity_settings + [
      // Ensure that we always have an empty array by default for the
      // 'handler_settings', regardless of the incoming form values.
      'handler_settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueSettings() {
    return $this->queue_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler() {
    return $this->handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerConfiguration() {
    return $this->handler_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandler($handler_id) {
    $this->handler = $handler_id;
    $this->getPluginCollection()->addInstanceID($handler_id, []);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerPlugin() {
    return $this->getPluginCollection()->get($this->handler);
  }

  /**
   * {@inheritdoc}
   */
  public function setHandlerPlugin($handler) {
    $this->getPluginCollection()->set($handler->getPluginId(), $handler);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['handler_configuration' => $this->getPluginCollection()];
  }

  /**
   * Encapsulates the creation of the EntityQueueHandlerPluginCollection.
   *
   * @return \Drupal\entityqueue\EntityQueueHandlerPluginCollection
   *   The entity queue's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->handlerPluginCollection) {
      $this->handlerPluginCollection = new EntityQueueHandlerPluginCollection(
        \Drupal::service('plugin.manager.entityqueue.handler'),
        $this->handler, $this->handler_configuration, $this);
    }
    return $this->handlerPluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Ensure that the queue depends on the module that provides the target
    // entity type.
    $target_entity_type = \Drupal::entityTypeManager()->getDefinition($this->getTargetEntityTypeId());
    $this->addDependency('module', $target_entity_type->getProvider());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->getHandlerPlugin()->onQueuePreSave($this, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $this->getHandlerPlugin()->onQueuePostSave($this, $storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $queue) {
      $queue->getHandlerPlugin()->onQueuePreDelete($queue, $storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $queue) {
      $queue->getHandlerPlugin()->onQueuePostDelete($queue, $storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);

    foreach ($entities as $queue) {
      $queue->getHandlerPlugin()->onQueuePostLoad($queue, $storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    // In addition to the parent implementation, we also need to invalidate
    // queue-specific cache tags.
    $tags = Cache::mergeTags($this->getEntityType()->getListCacheTags(), $this->getCacheTagsToInvalidate());

    Cache::invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   *
   * Override to never invalidate the individual entities' cache tags; the
   * config system already invalidates them.
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    $tags = $entity_type->getListCacheTags();

    // In addition to the parent implementation, we also need to invalidate
    // queue-specific cache tags.
    foreach ($entities as $entity) {
      $tags = Cache::mergeTags($tags, $entity->getCacheTagsToInvalidate());
    }

    Cache::invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    // A newly created or deleted queue could alter views data relationships, so
    // we must invalidate the associated 'views_data' cache tag.
    return Cache::mergeTags(parent::getCacheTagsToInvalidate(), ['views_data', 'entity_field_info']);
  }

  /**
   * {@inheritdoc}
   *
   * @return static[]
   *   An array of entity queue objects, indexed by their IDs.
   */
  public static function loadMultipleByTargetType($target_entity_type_id) {
    $ids = \Drupal::entityTypeManager()->getStorage('entity_queue')->getQuery()
      ->condition('entity_settings.target_type', $target_entity_type_id)
      ->execute();

    return $ids ? static::loadMultiple($ids) : [];
  }

}
