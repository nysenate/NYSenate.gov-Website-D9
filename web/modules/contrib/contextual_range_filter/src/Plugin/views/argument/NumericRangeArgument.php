<?php

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\contextual_range_filter\ContextualRangeFilter;

/**
 * Argument handler to accept a numeric range.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("numeric_range")
 */
class NumericRangeArgument extends NumericArgument {

  use MultiRangesTrait;

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['description']['#markup'] = t('Contextual range filter values are taken from the URL.');

    $form['more']['#open'] = TRUE;

    $form['break_phrase']['#title'] = t('Allow multiple numeric ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>29--29.95+100--250</strong>');

    $form['not']['#description'] = t('Negate the range. If selected, output matching the specified numeric range(s) will be excluded, rather than included.');
  }

  /**
   * Title override.
   *
   * Required because of range version of breakPhrase() in this function.
   */
  public function title() {
    if (!$this->argument) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = [$this->argument];
      $this->operator = 'or';
    }
    if ($this->value === FALSE) {
      return $this->definition['invalid input'] ?: t('Invalid input');
    }
    if (empty($this->value)) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }
    return implode($this->operator == 'or' ? ' + ' : ', ', $this->value);
  }

  /**
   * Create the query.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Check "Allow multple ranges" checkbox.
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = [$this->argument];
    }
    ContextualRangeFilter::buildRangeQuery($this);
  }

}
