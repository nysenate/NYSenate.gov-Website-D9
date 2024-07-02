<?php

namespace Drupal\views_combine\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * A handler to filter combined views.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_combine")
 */
class Combine extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected $valueFormType = 'select';

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Views');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['default_views'] = ['default' => []];
    $options['expose']['contains']['all_views'] = ['default' => []];
    $options['expose']['contains']['view_labels'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['default_views'] = [];
    $this->options['expose']['all_views'] = [];
    $this->options['expose']['view_labels'] = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['default_views'] = [
      '#type' => 'select',
      '#title' => $this->t('Default views override'),
      '#description' => $this->t('Choose the default views. Leave blank to disable.'),
      '#options' => $this->getValueOptions(),
      '#multiple' => TRUE,
      '#default_value' => $this->options['expose']['default_views'],
      '#states' => [
        'visible' => [
          ':input[name="options[expose][reduce]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['expose']['default_views']['#size'] = count($form['expose']['default_views']['#options']);
    $form['expose']['all_views'] = [
      '#type' => 'select',
      '#title' => $this->t('All views override'),
      '#description' => $this->t('Choose views for the select all option. Leave blank to disable.'),
      '#options' => $this->getValueOptions(),
      '#multiple' => TRUE,
      '#default_value' => $this->options['expose']['all_views'],
    ];
    $form['expose']['all_views']['#size'] = count($form['expose']['all_views']['#options']);
    $form['expose']['view_labels'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Rewrite views label'),
      '#description' => $this->t('Rewrite views option label using the "value | Label" format. One per line.'),
      '#default_value' => $this->options['expose']['view_labels'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions['current_view'] = 'current_view';
      foreach ($this->view->display_handler->getHandlers('field') as $field_handler) {
        if ($field_handler->getPluginId() === 'views_combine') {
          $this->valueOptions[$field_handler->options['view_id']] = str_replace(':', ' : ', $field_handler->options['view_id']);
        }
      }
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      return;
    }

    // Set default views selections. Only applicable if the reduce option
    // is enabled on the exposed filter. Otherwise, default selections derive
    // from the value element itself.
    if ($this->options['expose']['default_views'] && !$this->view->getExposedInput()) {
      $default_value = $this->options['expose']['default_views'];
      $this->value = $this->options['expose']['multiple'] ? $default_value : current($default_value);
      $form_state->setUserInput([$this->options['expose']['identifier'] => $this->value]);
    }

    // Rewrite view option labels.
    if ($labels = $this->getViewLabels()) {
      array_walk($form['value']['#options'], function (&$option, $key) use ($labels) {
        if (isset($labels[$key])) {
          $option = $labels[$key];
        }
      });
    }
  }

  /**
   * Get rewritten view option labels.
   *
   * @return array
   *   Returns associative array of labels keyed by value.
   */
  protected function getViewLabels() {
    $values = [];
    if (!empty($this->options['expose']['view_labels'])) {
      foreach (preg_split("/(\r\n|\n|\r)/", $this->options['expose']['view_labels']) as $raw_value) {
        @[$key, $label] = array_map('trim', explode('|', $raw_value));
        if (!empty($key) && !empty($label)) {
          $values[str_replace(' : ', ':', $key)] = $label;
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views_combine\ViewsCombiner::setFilters()
   */
  public function query() {
    // Stop! In the name of Drupal.
  }

}
