<?php

namespace Drupal\entityqueue;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class that builds a listing of entity subqueues.
 */
class EntitySubqueueListBuilder extends EntityListBuilder {

  /**
   * The ID of the entity queue for which to list all subqueues.
   *
   * @var \Drupal\entityqueue\Entity\EntityQueue
   */
  protected $queueId;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * Sets the entity queue ID.
   *
   * @param string $queue_id
   *   The entity queue ID.
   *
   * @return $this
   */
  public function setQueueId($queue_id) {
    $this->queueId = $queue_id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    foreach ($entities as $key => $entity) {
      $entities[$key] = $this->entityRepository->getTranslationFromContext($entity);
    }
    return $entities;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id and optionally
   * filtered by bundle.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    if ($this->queueId) {
      $query->condition($this->entityType->getKey('bundle'), $this->queueId);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Subqueue');
    $header['items'] = $this->t('Items');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['items'] = $this->formatPlural(count($entity->items), '@count item', '@count items');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['edit']['title'] = $this->t('Edit items');

    /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
    $queue = $entity->getQueue();

    // Add the 'edit queue' operation as well.
    if ($queue->access('update') && $queue->hasLinkTemplate('edit-form')) {
      $operations['configure'] = [
        'title' => $this->t('Configure queue'),
        'weight' => 10,
        'url' => $this->ensureDestination($queue->toUrl('edit-form')),
      ];
    }

    return $operations;
  }

}
