<?php

/**
 * @file
 * hook_post_update_NAME functions for entity_usage module.
 */

use Drupal\Core\Url;
use Drupal\Core\Site\Settings;
use Drupal\entity_usage\Controller\ListUsageController;

/**
 * Implements hook_post_update_NAME().
 * Re-generate entity_usage statistics.
 */
function entity_usage_post_update_regenerate_2x(&$sandbox) {
  if (!\Drupal::state()->get('entity_usage_2x_regenerate')) {
    return;
  }

  // First pass.
  if (empty($sandbox['total'])) {
    $sandbox['current_key'] = 0;
    $sandbox['total'] = 0;
    $sandbox['entities'] = [];

    $to_track = \Drupal::config('entity_usage.settings')->get('track_enabled_source_entity_types');
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
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
        // Delete current usage statistics for these entities.
        \Drupal::service('entity_usage.usage')->bulkDeleteSources($entity_type_id);
        // Add all existing ids to be tracked again.
        $ids = \Drupal::entityQuery($entity_type_id)
          ->accessCheck(FALSE)
          ->execute();
        if (!empty($ids)) {
          $sandbox['total'] += count($ids);
          foreach ($ids as $id) {
            $sandbox['entities'][] = [
              'entity_type' => $entity_type_id,
              'entity_id' => $id,
            ];
          }
        }
      }
    }
  }

  // Abort the batch process if the site is big enough for this process to be
  // a very long-running process.
  $limit = Settings::get('entity_usage_2x_regenerate_limit', 2000);
  if ($sandbox['total'] > $limit) {
    $sandbox = [];
    return t('The automatic regeneration of usage statistics was skipped because it could be potentially slow on this site. Make sure you visit the <a href="@batch_url">batch update</a> page and trigger the update manually.', [
      '@batch_url' => Url::fromRoute('entity_usage.batch_update')->toString(),
    ]);
  }

  // Worker.
  $batch_size = 1;
  for ($i = $sandbox['current_key']; $i < ($sandbox['current_key'] + $batch_size); $i++) {
    if (empty($sandbox['entities'][$i])) {
      break;
    }
    $entity_type = $sandbox['entities'][$i]['entity_type'];
    $entity_id = $sandbox['entities'][$i]['entity_id'];
    if ($entity_type && $entity_id) {
      $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $entity_storage->load($entity_id);
      if ($entity->getEntityType()->isRevisionable()) {
        // Track all revisions and translations of the source entity. Sources
        // are tracked as if they were new entities.
        $result = $entity_storage->getQuery()
          ->allRevisions()
          ->condition($entity->getEntityType()->getKey('id'), $entity->id())
          ->sort($entity->getEntityType()->getKey('revision'), 'DESC')
          ->execute();
        $revision_ids = array_keys($result);

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
    $sandbox['current_key']++;
  }

  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current_key'] / $sandbox['total']);
  return t('Finished generating statistics for @total_count entities.', [
    '@total_count' => $sandbox['total'],
  ]);
}
