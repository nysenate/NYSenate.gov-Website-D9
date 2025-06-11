<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposed filter to show "Issue" terms linked to the active senator.
 *
 * @ViewsFilter("nys_senator_dashboard_active_senator_used_issues")
 */
class ActiveSenatorUsedIssues extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $active_senator = \Drupal::service('nys_senator_dashboard.managed_senators_handler')->getActiveSenator(FALSE);
    if (!$active_senator) {
      return;
    }
    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#title' => $this->t('Filter By'),
      '#options' => [
        $active_senator->id() => $this->t('Issues used by') . ' ' . $active_senator->label(),
      ],
      '#default_value' => !empty($this->value) ? $this->value : '',
      '#empty_option' => $this->t('- All issues -'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->value)) {
      $value = reset($this->value);
      $active_senator_id = \Drupal::service('nys_senator_dashboard.managed_senators_handler')->getActiveSenator();
      // These should always match.
      if ($value == $active_senator_id) {
        $this->query->addWhereExpression(
          $this->options['group'],
          "EXISTS (
            SELECT 1 FROM {node_field_data} nfd
            INNER JOIN {taxonomy_index} ti ON nfd.nid = ti.nid
            INNER JOIN {node__field_senator_multiref} senator_ref ON nfd.nid = senator_ref.entity_id
            WHERE ti.tid = taxonomy_term_field_data.tid
              AND senator_ref.field_senator_multiref_target_id = :active_senator
          )",
          [':active_senator' => $value]
        );
      }
    }
  }

}
