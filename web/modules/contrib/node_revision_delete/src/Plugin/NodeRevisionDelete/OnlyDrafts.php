<?php

namespace Drupal\node_revision_delete\Plugin\NodeRevisionDelete;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node_revision_delete\Plugin\NodeRevisionDeleteBase;

/**
 * Determines whether to delete draft revisions newer than the active revision.
 *
 * @NodeRevisionDelete(
 *  id = "only_drafts",
 *  label = @Translation("Delete only drafts as specified."),
 * )
 */
class OnlyDrafts extends NodeRevisionDeleteBase {

  /**
   * {@inheritdoc}
   */
  public function checkRevisions(array $revision_ids, int $active_vid): array {
    $revision_statuses = [];

    foreach ($revision_ids as $vid) {
      $revision_id = $vid;
      /** @var \Drupal\node\NodeInterface $revision */
      $revision = $this->entityTypeManager->getStorage('node')->loadRevision($revision_id);
      $can_delete = NULL;

      $age = strtotime('-' . $this->configuration['age'] . 'months');

      // The timestamp of the created revision is stored in the changed field.
      $creation_time = $revision->getChangedTime();

      // Explicitly keep draft revisions for the configured minimum age. We only
      // have an opinion on draft revisions created before the active revision.
      if ($revision_id < $active_vid && $creation_time >= $age) {
        $can_delete = FALSE;
      }
      elseif ($revision_id < $active_vid && $creation_time < $age) {
        $revision_state = $revision->get('moderation_state')->getString();
        $expected_state = 'draft';
        if (str_contains($revision_state, $expected_state)) {
          $can_delete = TRUE;
        }
      }

      $revision_statuses[$revision_id] = $can_delete;
    }

    return $revision_statuses;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $options = [1 => '1 ' . $this->t('month')];
    for ($i = 2; $i <= 24; $i++) {
      $options[$i] = $i . ' ' . $this->t('months');
    }
    $options[0] = '0 ' . $this->t('months');
    $form['age'] = [
      '#type' => 'select',
      '#title' => $this->t('The minimum amount of months a draft revision must be kept for'),
      '#description' => $this->t('After this time, draft revisions newer than the active revision can be deleted. The minimum age of revisions is always respected, regardless of other settings. Only draft revisions created after the active revision will be deleted.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->configuration['age'] ?? 0,
    ];
    return $form;
  }

}
