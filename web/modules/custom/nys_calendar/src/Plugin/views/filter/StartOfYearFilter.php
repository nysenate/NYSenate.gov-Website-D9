<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Start of year filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("nys_start_of_year")
 */
class StartOfYearFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options['selected_year'] = ['default' => NULL];
    $options['show_content_from'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $current_year = date('Y');
    $years = range($current_year, $current_year - 30);
    $options = ['current_year' => 'Current year'] + array_combine($years, $years);
    $form['selected_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Selected year'),
      '#options' => $options,
    ];
    $form['show_content_from'] = [
      '#type' => 'select',
      '#title' => $this->t('Show content from'),
      '#options' => [
        'after_selected_year' => 'After selected year',
        'before_selected_year' => 'Before selected year',
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    // @todo implement query where conditions, based on $this->options values
    //   1. If view filtering on NON-bills/resolutions, filter on created date
    //   2. If filtering on bills/resolutions, filter on session field
  }

}
