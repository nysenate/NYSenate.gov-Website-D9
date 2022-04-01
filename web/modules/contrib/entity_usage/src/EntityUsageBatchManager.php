<?php

namespace Drupal\entity_usage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manages Entity Usage integration with Batch API.
 */
class EntityUsageBatchManager implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The size of the batch for the revision queries.
   */
  const REVISION_BATCH_SIZE = 15;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity usage configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Creates a EntityUsageBatchManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->config = $config_factory->get('entity_usage.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('config.factory')
    );
  }

  /**
   * Recreate the entity usage statistics.
   *
   * Generate a batch to recreate the statistics for all entities.
   * Note that if we force all statistics to be created, there is no need to
   * separate them between source / target cases. If all entities are
   * going to be re-tracked, tracking all of them as source is enough, because
   * there could never be a target without a source.
   */
  public function recreate() {
    $batch = $this->generateBatch();
    batch_set($batch);
  }

  /**
   * Create a batch to process the entity types in bulk.
   *
   * @return array
   *   The batch array.
   */
  public function generateBatch() {
    $operations = [];
    $to_track = $to_track = $this->config->get('track_enabled_source_entity_types');
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Only look for entities enabled for tracking on the settings form.
      $track_this_entity_type = FALSE;
      if (!is_array($to_track) && ($entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface'))) {
        // When no settings are defined, track all content entities by default,
        // except for Files and Users.
        if (!in_array($entity_type_id, ['file', 'user'])) {
          $track_this_entity_type = TRUE;
        }
      }
      elseif (is_array($to_track) && in_array($entity_type_id, $to_track, TRUE)) {
        $track_this_entity_type = TRUE;
      }
      if ($track_this_entity_type) {
        $operations[] = ['\Drupal\entity_usage\EntityUsageBatchManager::updateSourcesBatchWorker', [$entity_type_id]];
      }
    }

    $batch = [
      'operations' => $operations,
      'finished' => '\Drupal\entity_usage\EntityUsageBatchManager::batchFinished',
      'title' => $this->t('Updating entity usage statistics.'),
      'progress_message' => $this->t('Processed @current of @total entity types.'),
      'error_message' => $this->t('This batch encountered an error.'),
    ];

    return $batch;
  }

  /**
   * Batch operation worker for recreating statistics for source entities.
   *
   * @param string $entity_type_id
   *   The entity type id, for example 'node'.
   * @param mixed $context
   *   Batch context.
   */
  public static function updateSourcesBatchWorker($entity_type_id, &$context) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_type_key = $entity_type->getKey('id');

    if (empty($context['sandbox']['total'])) {
      // Delete current usage statistics for these entities.
      \Drupal::service('entity_usage.usage')->bulkDeleteSources($entity_type_id);

      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = -1;
      $context['sandbox']['total'] = (int) $entity_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      $context['sandbox']['batch_entity_revision'] = [
        'status' => 0,
        'current_vid' => 0,
        'start' => 0,
      ];
    }
    if ($context['sandbox']['batch_entity_revision']['status']) {
      $op = '=';
    }
    else {
      $op = '>';
    }

    $entity_ids = $entity_storage->getQuery()
      ->condition($entity_type_key, $context['sandbox']['current_id'], $op)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->sort($entity_type_key)
      ->execute();
    $entity_id = reset($entity_ids);

    if ($entity_id && $entity = $entity_storage->load($entity_id)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      try {
        if ($entity->getEntityType()->isRevisionable()) {
          // We cannot query the revisions due to this bug
          // https://www.drupal.org/project/drupal/issues/2766135
          // so we will use offsets.
          $start = $context['sandbox']['batch_entity_revision']['start'];
          // Track all revisions and translations of the source entity. Sources
          // are tracked as if they were new entities.
          $result = $entity_storage->getQuery()->allRevisions()
            ->condition($entity->getEntityType()->getKey('id'), $entity->id())
            ->accessCheck(FALSE)
            ->sort($entity->getEntityType()->getKey('revision'), 'DESC')
            ->range($start, static::REVISION_BATCH_SIZE)
            ->execute();
          $revision_ids = array_keys($result);
          if (count($revision_ids) === static::REVISION_BATCH_SIZE) {
            $context['sandbox']['batch_entity_revision'] = [
              'status' => 1,
              'current_vid' => min($revision_ids),
              'start' => $start + static::REVISION_BATCH_SIZE,
            ];
          }
          else {
            $context['sandbox']['batch_entity_revision'] = [
              'status' => 0,
              'current_vid' => 0,
              'start' => 0,
            ];
          }

          foreach ($revision_ids as $revision_id) {
            /** @var \Drupal\Core\Entity\EntityInterface $entity_revision */
            if (!$entity_revision = $entity_storage->loadRevision($revision_id)) {
              continue;
            }

            \Drupal::service('entity_usage.entity_update_manager')->trackUpdateOnCreation($entity_revision);
          }
        }
        else {
          // Sources are tracked as if they were new entities.
          \Drupal::service('entity_usage.entity_update_manager')->trackUpdateOnCreation($entity);
        }
      }
      catch (\Exception $e) {
        watchdog_exception('entity_usage.batch', $e);
      }

      if (
        $context['sandbox']['batch_entity_revision']['status'] === 0 ||
        intval($context['sandbox']['progress']) === 0
      ) {
        $context['sandbox']['progress']++;
      }
      $context['sandbox']['current_id'] = $entity->id();
      $context['results'][] = $entity_type_id . ':' . $entity->id();
    }

    if ($context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }

    $context['message'] = t('Updating entity usage for @entity_type: @current of @total', [
      '@entity_type' => $entity_type_id,
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Finish callback for our batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t('Recreated entity usage for @count entities.', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      \Drupal::messenger()->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
