<?php

namespace Drupal\entityqueue;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class that builds a listing of entity queues.
 */
class EntityQueueListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];
    /** @var \Drupal\entityqueue\EntityQueueInterface $entity */
    foreach (parent::load() as $entity) {
      // Don't display queues which can not be edited by the user.
      if (!$entity->access('update')) {
        continue;
      }
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Queue name');
    $header['target_type'] = $this->t('Target type');
    $header['handler'] = $this->t('Queue type');
    $header['items'] = $this->t('Items');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof EntityQueueInterface);
    $row = [
      'data' => [
        'label' => $entity->label(),
        'target_type' => $this->entityTypeManager->getDefinition($entity->getTargetEntityTypeId())->getLabel(),
        'handler' => $entity->getHandlerPlugin()->getPluginDefinition()['title'],
        'items' => $this->getQueueItemsStatus($entity),
      ] + parent::buildRow($entity),
      'title' => $this->t('Machine name: @name', ['@name' => $entity->id()]),
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();

    $build['#type'] = 'container';
    $build['#attributes']['id'] = 'entity-queue-list';
    $build['#attached']['library'][] = 'core/drupal.ajax';
    $build['#cache'] = [
      'contexts' => Cache::mergeContexts($this->entityType->getListCacheContexts(), ['user.permissions']),
      'tags' => $this->entityType->getListCacheTags(),
    ];

    $build['enabled']['heading']['#markup'] = '<h2>' . $this->t('Enabled', [], ['context' => 'Plural']) . '</h2>';
    $build['disabled']['heading']['#markup'] = '<h2>' . $this->t('Disabled', [], ['context' => 'Plural']) . '</h2>';

    foreach (['enabled', 'disabled'] as $status) {
      $build[$status]['#type'] = 'container';
      $build[$status]['#attributes'] = ['class' => ['entity-queue-list-section', $status]];
      $build[$status]['table'] = [
        '#type' => 'table',
        '#attributes' => [
          'class' => ['entity-queue-listing-table'],
        ],
        '#header' => $this->buildHeader(),
        '#rows' => [],
        '#cache' => [
          'contexts' => $this->entityType->getListCacheContexts(),
          'tags' => $this->entityType->getListCacheTags(),
        ],
      ];
      foreach ($entities[$status] as $entity) {
        $build[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    // @todo Use a placeholder for the entity label if this is abstracted to
    //   other entity types.
    $build['enabled']['table']['#empty'] = $this->t('There are no enabled queues.');
    $build['disabled']['table']['#empty'] = $this->t('There are no disabled queues.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    assert($entity instanceof EntityQueueInterface);
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    // Add AJAX functionality to enable/disable operations.
    foreach (['enable', 'disable'] as $op) {
      if (isset($operations[$op])) {
        $operations[$op]['url'] = $entity->toUrl($op);
        // Enable and disable operations should use AJAX.
        $operations[$op]['attributes']['class'][] = 'use-ajax';
      }
    }

    // Allow queue handlers to add their own operations.
    $operations += $entity->getHandlerPlugin()->getQueueListBuilderOperations();

    return $operations;
  }

  /**
   * Returns the number of items in a subqueue or the number of subqueues.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   An entity queue object.
   *
   * @return string
   *   The number of items in a subqueue or the number of subqueues.
   */
  protected function getQueueItemsStatus(EntityQueueInterface $queue) {
    $handler = $queue->getHandlerPlugin();

    $items = NULL;
    if ($handler->supportsMultipleSubqueues()) {
      $subqueues_count = $this->entityTypeManager->getStorage('entity_subqueue')->getQuery()
        ->condition('queue', $queue->id(), '=')
        ->count()
        ->execute();

      $items = $this->formatPlural($subqueues_count, '@count subqueue', '@count subqueues');
    }
    else {
      $subqueue = EntitySubqueue::load($queue->id());

      $items = $this->formatPlural(count($subqueue->items), '@count item', '@count items');
    }

    return $items;
  }

}
