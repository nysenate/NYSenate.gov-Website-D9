<?php

namespace Drupal\other_view_filter\Plugin\views\filter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter content by other view results set.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("other_views_filter")
 */
class OtherView extends InOperator {

  /**
   * Value element type.
   *
   * @var string
   */
  protected $valueFormType = 'select';

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * Any arguments that have been passed into the view.
   *
   * @var array
   */
  protected $defaultArgs;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $view_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewStorage = $view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->defaultArgs = $view->args;
    $this->valueTitle = $this->t('View: display');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Set "not in" operator to have usual use case selected by default.
    $options['operator']['default'] = 'not in';
    $options['inherit_contextual_filters'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['inherit_contextual_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inherit contextual filter(s)'),
      '#default_value' => $this->options['inherit_contextual_filters'],
      '#description' => $this->t('Use the parent contextual filter(s) for other views result.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    if (isset($form['value']['#options']['all'])) {
      unset($form['value']['#options']['all']);
    }

    $form['user_warning'] = [
      '#markup' => $this->t(
        'Using more than 1 view in "@input" section will strongly decrease your site performance.',
        ['@input' => $this->t('View: display')]
      ),
      '#prefix' => '<div class="messages messages--warning">',
      '#suffix' => '</div>',
      '#weight' => -999,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [];
    $views = $this->viewStorage->loadMultiple();

    foreach ($views as $view) {
      if ($view->get('base_table') === $this->table && $view->get('base_field') === $this->realField) {
        foreach ($view->get('display') as $display_id => $display) {
          $this->valueOptions[$view->id() . ':' . $display_id] = $view->label() . ': ' . $display['display_title'];
        }
      }
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    $results = $this->getOtherViewsResults($this->value);

    // Apply filter if selected views return some results.
    if ($results) {
      $this->value = $results;
      parent::opSimple();
    }
    // Prevent any results display if selected views return empty result.
    elseif ($this->operator === 'in') {
      $this->query->addWhereExpression($this->options['group'], '1 = 2');
    }
  }

  /**
   * Return requested views results.
   *
   * @param array $views
   *   List of views and displays.
   *
   * @return array
   *   Requested views results.
   */
  protected function getOtherViewsResults(array $views) {
    $results = [];

    if (empty($views)) {
      return $results;
    }

    foreach ($views as $view_display) {
      list($name, $display) = explode(':', $view_display, 2);

      // Get the results of the specified view/display combo.
      if ($name && $display) {
        $view = $this->viewStorage->load($name);

        if (!($view instanceof ViewEntityInterface)) {
          continue;
        }

        $view = $view->getExecutable();

        if (!($view instanceof ViewExecutable) || !$view->access($display)) {
          continue;
        }

        if ($this->options['inherit_contextual_filters'] && !empty($this->defaultArgs)) {
          $view->setArguments($this->defaultArgs);
        }
        $view->setDisplay($display);
        $view->preExecute();
        $view->execute();

        if (empty($view->result)) {
          continue;
        }

        foreach ($view->result as $row) {
          if (isset($row->{$this->realField})) {
            $results[$row->{$this->realField}] = $row->{$this->realField};
          }
        }
      }
    }

    return $results ? array_values($results) : [];
  }

}
