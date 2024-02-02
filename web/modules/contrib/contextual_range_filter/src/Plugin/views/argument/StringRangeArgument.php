<?php

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contextual_range_filter\ContextualRangeFilter;

/**
 * Argument handler to accept a string range.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("string_range")
 */
class StringRangeArgument extends StringArgument {

  use MultiRangesTrait;

  /**
   * Define our options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Add 'Exclude' tick box, as it is not supplied by the String base class.
    $options['not'] = ['default' => FALSE, 'bool' => TRUE];
    return $options;
  }

  /**
   * Build the form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['description']['#markup'] = t('Contextual string range filter values are taken from the URL.');

    $form['more']['#open'] = TRUE;

    $form['break_phrase']['#title'] = t('Allow multiple string (i.e. alphabetic) ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>a--f+q--y</strong>');

    $form['not'] = [
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified range(s) will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    ];
  }

  /**
   * Build the query.
   */
  public function query($group_by = FALSE) {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }
    // Check "Allow multple ranges" checkbox.
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = [$argument];
    }
    $this->ensureMyTable();

    if (!empty($this->definition['many to one'])) {
      if (!empty($this->options['glossary'])) {
        $this->helper->formula = TRUE;
      }
      $this->helper->ensureMyTable();
      $this->helper->addFilter();
      return;
    }

    if (empty($this->options['glossary'])) {
      $field = "$this->tableAlias.$this->realField";
    }
    else {
      $field = $this->getFormula();
    }
    ContextualRangeFilter::buildRangeQuery($this, $field);
  }

}
