<?php

namespace Drupal\node_revision_delete\Plugin\NodeRevisionDelete;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node_revision_delete\Plugin\NodeRevisionDeleteBase;

/**
 * Determines whether to delete a revision based on the amount of revisions.
 *
 * @NodeRevisionDelete(
 *  id = "amount",
 *  label = @Translation("Delete revisions when a certain amount of revisions is reached."),
 * )
 */
class Amount extends NodeRevisionDeleteBase {

  /**
   * {@inheritdoc}
   */
  public function checkRevisions(array $revision_ids, int $active_vid): array {
    $revision_statuses = [];

    $count = 0;
    foreach ($revision_ids as $vid) {
      $revision_id = $vid;
      $can_delete = NULL;

      $amount = ($this->configuration['amount'] ?? 1) ?: 1;

      // Since we always keep the active revision, we need to subtract 1 from
      // the configured amount.
      if ($amount > 0) {
        --$amount;
      }

      // We only have an opinion on revisions created before the active
      // revision.
      if ($revision_id < $active_vid) {
        $count++;
      }

      // Explicitely keep a minimum amount of revisions. We only have an opinion
      // on revisions created before the active revision.
      if ($revision_id < $active_vid && $count <= $amount) {
        $can_delete = FALSE;
      }
      elseif ($revision_id < $active_vid && $count > $amount) {
        $can_delete = TRUE;
      }

      $revision_statuses[$revision_id] = $can_delete;
    }

    return $revision_statuses;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum number of revisions to keep (per language)'),
      '#description' => $this->t('After the amount is reached, older revisions can be deleted. The minimum amount of revisions is always respected, regardless of other settings. Inactive revisions (like drafts) created after the active revision will not be deleted.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['amount'] ?? 0,
    ];
    return $form;
  }

}
