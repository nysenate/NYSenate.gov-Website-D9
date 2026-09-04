<?php

namespace Drupal\nys_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display the bill vote widget (simple mode).
 *
 * The vote_widget_simple field is a Manage-Display pseudo-field
 * (hook_entity_extra_field_info() in nys_bill_vote.module), not a real
 * Field API field, so it can't be added via the normal Views field system.
 * This plugin renders it directly via the same lazy builder the module's
 * own hook_ENTITY_TYPE_view_alter() uses, bypassing entity view mode
 * rendering entirely so the output is bare (no wrapping node markup).
 *
 * @ViewsField("bill_vote_widget_simple")
 */
class BillVoteWidgetSimple extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function clickSortable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values);
    if (!$nid) {
      return '';
    }
    return [
      '#lazy_builder' => [
        'nys_bill_vote.vote_widget_lazy_builder:renderVoteWidget',
        [(int) $nid, TRUE, FALSE],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

}
