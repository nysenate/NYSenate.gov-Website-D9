<?php

namespace Drupal\views_exclude_previous\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter exclude previous viewed node's.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_exclude_previous")
 */
class ViewsExcludePreviousNodes extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // @todo Make this pluggable.
    $this->valueOptions = [
      'node_load' => 'Exclude nodes previously loaded (hook_node_load).',
      'node_view' => 'Exclude nodes previously viewd (hook_node_view).',
      'views' => 'Exclude nodes that where loaded in any node based view.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'not in';
    $options['value']['default'] = [];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title') {
    return [
      'not in' => $this->t('Is not in'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Exclude all node's yet displayed.
   */
  public function query() {
    $alias = $this->query->ensureTable('node');
    if (!$alias) {
      return;
    }
    if (!$this->value) {
      return;
    }
    $excludes = [];
    foreach ($this->value as $category) {
      $excludes += views_exclude_previous_remove($category);
    }

    if (!empty($excludes)) {
      $this->query->addWhere($this->options['group'], $alias . '.nid', $excludes, 'NOT IN');
    }
  }

}
