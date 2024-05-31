<?php

namespace Drupal\entityqueue\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entityqueue\EntityQueueInterface;
use Drupal\entityqueue\EntitySubqueueInterface;
use Drupal\entityqueue\EntitySubqueueItemsFieldItemList;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the EntitySubqueue entity class.
 *
 * @ContentEntityType(
 *   id = "entity_subqueue",
 *   label = @Translation("Entity subqueue"),
 *   label_collection = @Translation("Entity subqueues"),
 *   label_singular = @Translation("subqueue"),
 *   label_plural = @Translation("subqueues"),
 *   label_count = @PluralTranslation(
 *     singular = "@count subqueue",
 *     plural = "@count subqueues"
 *   ),
 *   bundle_label = @Translation("Entity queue"),
 *   handlers = {
 *     "storage" = "\Drupal\entityqueue\EntitySubqueueStorage",
 *     "form" = {
 *       "default" = "Drupal\entityqueue\Form\EntitySubqueueForm",
 *       "add" = "Drupal\entityqueue\Form\EntitySubqueueForm",
 *       "edit" = "Drupal\entityqueue\Form\EntitySubqueueForm",
 *       "delete" = "\Drupal\entityqueue\Form\EntitySubqueueDeleteForm",
 *     },
 *     "access" = "Drupal\entityqueue\EntitySubqueueAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\entityqueue\Routing\EntitySubqueueRouteProvider",
 *     },
 *     "list_builder" = "Drupal\entityqueue\EntitySubqueueListBuilder",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "entity_subqueue",
 *   data_table = "entity_subqueue_field_data",
 *   revision_table = "entity_subqueue_revision",
 *   revision_data_table = "entity_subqueue_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "name",
 *     "revision" = "revision_id",
 *     "bundle" = "queue",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   bundle_entity_type = "entity_queue",
 *   field_ui_base_route = "entity.entity_queue.edit_form",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/admin/structure/entityqueue/{entity_queue}/{entity_subqueue}",
 *     "edit-form" = "/admin/structure/entityqueue/{entity_queue}/{entity_subqueue}",
 *     "delete-form" = "/admin/structure/entityqueue/{entity_queue}/{entity_subqueue}/delete",
 *     "collection" = "/admin/structure/entityqueue/{entity_queue}/list",
 *   },
 *   constraints = {
 *     "QueueSize" = {}
 *   }
 * )
 */
class EntitySubqueue extends EditorialContentEntityBase implements EntitySubqueueInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      return parent::access($operation, $account, $return_as_object);
    }

    return \Drupal::entityTypeManager()
      ->getAccessControlHandler($this->entityTypeId)
      ->access($this, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
    $queue = $this->getQueue();
    $max_size = $queue->getMaximumSize();

    // Remove extra items from the front of the queue if the maximum size is
    // exceeded.
    $items = $this->get('items')->getValue();
    if ($queue->getActAsQueue() && count($items) > $max_size) {
      if ($queue->isReversed()) {
        $items = array_slice($items, 0, $max_size);
      }
      else {
        $items = array_slice($items, -$max_size);
      }

      $this->set('items', $items);
    }

    // If no revision author has been set explicitly, make the subqueue owner
    // the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueue() {
    return $this->get('queue')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueue(EntityQueueInterface $queue) {
    $this->set('queue', $queue->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The ID (machine name) of the subqueue.'))
      ->setReadOnly(TRUE)
      // In order to work around the InnoDB 191 character limit on utf8mb4
      // primary keys, we set the character set for the field to ASCII.
      ->setSetting('is_ascii', TRUE);

    $fields['queue']->setDescription(t('The queue (bundle) of this subqueue.'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 191)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Items'))
      ->setClass(EntitySubqueueItemsFieldItemList::class)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      // This setting is overridden per bundle (queue) in
      // static::bundleFieldDefinitions(), but we need to default to a target
      // entity type that uses strings IDs, in order to allow both integers and
      // strings to be stored by the default entity reference field storage.
      ->setSetting('target_type', 'entity_subqueue')
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entityqueue_dragtable',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the subqueue was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the subqueue was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Keep this field hidden until we have a generic revision UI.
    // @see https://www.drupal.org/project/drupal/issues/2350939
    $fields['revision_log_message']->setDisplayOptions('form', [
      'region' => 'hidden',
    ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Change the target type of the 'items' field to the one defined by the
    // parent queue (i.e. bundle).
    if ($queue = EntityQueue::load($bundle)) {
      $fields['items'] = clone $base_field_definitions['items'];
      $fields['items']->setSettings($queue->getEntitySettings());

      return $fields;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(EntityInterface $entity) {
    $this->get('items')->appendItem($entity->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(EntityInterface $entity) {
    $index = $this->getItemPosition($entity);
    if ($index !== FALSE) {
      $this->get('items')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem(EntityInterface $entity) {
    return $this->getItemPosition($entity) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemPosition(EntityInterface $entity) {
    $subqueue_items = $this->get('items')->getValue();
    $subqueue_items_ids = array_map(function ($item) {
      return $item['target_id'];
    }, $subqueue_items);

    return array_search($entity->id(), $subqueue_items_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function reverseItems() {
    $subqueue_items = $this->get('items')->getValue();
    $this->get('items')->setValue(array_reverse($subqueue_items));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shuffleItems() {
    $subqueue_items = $this->get('items')->getValue();
    shuffle($subqueue_items);
    $this->get('items')->setValue($subqueue_items);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearItems() {
    $this->get('items')->setValue(NULL);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $url = parent::toUrl($rel, $options);

    // The 'entity_queue' parameter is needed by the subqueue routes, so we need
    // to add it manually.
    $url->setRouteParameter('entity_queue', $this->bundle());

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = [];

    // Use the cache tags of the entity queue.
    // @todo Allow queue handlers to control this?
    if ($queue = $this->getQueue()) {
      $tags = Cache::mergeTags(parent::getCacheTagsToInvalidate(), $queue->getCacheTags());

      // Sadly, Views handlers have no way of influencing the cache tags of the
      // views result cache plugins, so we have to invalidate the target entity
      // type list tag.
      // @todo Reconsider this when https://www.drupal.org/node/2710679 is fixed.
      $target_entity_type = $this->entityTypeManager()->getDefinition($this->getQueue()->getTargetEntityTypeId());
      $tags = Cache::mergeTags($tags, $target_entity_type->getListCacheTags());
    }

    return $tags;
  }

}
