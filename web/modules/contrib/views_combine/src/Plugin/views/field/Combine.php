<?php

namespace Drupal\views_combine\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * A handler to combine (unionize) views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_combine")
 */
class Combine extends FieldPluginBase {

  /**
   * The handler types with map options.
   */
  const MAP_HANDLER_TYPES = ['filter', 'sort'];

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->options['exclude'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->options['view_id'];
  }

  /**
   * Get exposed views handlers.
   *
   * @param string $handler_type
   *   The views handler type.
   *
   * @return array
   *   Returns array of views exposed handlers.
   */
  public function getExposedHandlers(string $handler_type) {
    return array_filter(
      $this->view->getHandlers($handler_type),
      function ($handler) {
        return isset($handler['exposed']) && $handler['exposed'];
      }
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_id'] = ['default' => ''];
    foreach (self::MAP_HANDLER_TYPES as $handler_type) {
      $key = "{$handler_type}_map";
      $options[$key] = [];
      foreach ($this->getExposedHandlers($handler_type) as $id => $definition) {
        $options[$key][$id] = ['default' => ''];
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptionDefaults(array &$storage, array $options) {
    foreach ($options as &$definition) {
      if (!isset($definition['default'])) {
        $definition['default'] = '';
      }
    }
    parent::setOptionDefaults($storage, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = Views::getViewsAsOptions(FALSE, 'enabled', NULL, TRUE, TRUE);
    $form['view_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Combine view'),
      '#description' => $this->t('The view to combine into this view.'),
      '#options' => $options,
      '#default_value' => $this->options['view_id'],
      '#required' => TRUE,
    ];
    foreach (self::MAP_HANDLER_TYPES as $handler_type) {
      $this->buildMap($handler_type, $form);
    }
  }

  /**
   * Builds the views handler map elements.
   *
   * @param string $handler_type
   *   The views handler type.
   * @param array $form
   *   The form.
   */
  protected function buildMap(string $handler_type, array &$form) {
    if ($handlers = $this->getExposedHandlers($handler_type)) {
      $key = "{$handler_type}_map";
      $title = mb_strtolower(ViewExecutable::getHandlerTypes()[$handler_type]['title']);
      $form[$key] = [
        '#type' => 'details',
        '#title' => $this->t('Map exposed @title', ['@title' => $title]),
        '#description' => $this->t('Map exposed @title input identifiers from this view to the combined view.', [
          '@title' => $title,
        ]),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      foreach ($handlers as $id => $definition) {
        $form[$key][$id] = [
          '#type' => 'textfield',
          '#title' => $id,
          '#default_value' => $this->options[$key][$id] ?? '',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see views_combine_views_pre_execute()
   */
  public function query() {
    // Do nothing. Sit back, relax, have a mojito.
  }

}
