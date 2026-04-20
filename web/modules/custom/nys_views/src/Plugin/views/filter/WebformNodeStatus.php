<?php

namespace Drupal\nys_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by webform node-level status override.
 *
 * The node__webform.webform_status column stores 'open', 'closed',
 * 'scheduled', or NULL (inherit from the webform entity). This filter
 * provides a select dropdown instead of the default plain text field.
 *
 * @ViewsFilter("webform_node_status")
 */
class WebformNodeStatus extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): string {
    $val = is_array($this->value) ? reset($this->value) : $this->value;
    return match($val) {
      'open' => (string) $this->t('Open'),
      'closed' => (string) $this->t('Closed'),
      'scheduled' => (string) $this->t('Scheduled'),
      default => (string) $this->t('All'),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $val = is_array($this->value) ? reset($this->value) : ($this->value ?? 'All');
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        'All' => $this->t('- Any -'),
        'open' => $this->t('Open'),
        'closed' => $this->t('Closed'),
        'scheduled' => $this->t('Scheduled'),
      ],
      '#default_value' => $val,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Views wraps exposed filter values in an array via acceptExposedInput().
    $val = is_array($this->value) ? reset($this->value) : $this->value;
    if ($val === NULL || $val === '' || $val === FALSE || $val === 'All') {
      return;
    }
    $this->ensureMyTable();
    $this->query->addWhere($this->options['group'], "$this->tableAlias.webform_status", $val, '=');
  }

}
