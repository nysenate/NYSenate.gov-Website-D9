<?php

namespace Drupal\views_combine\Plugin\views\sort;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Handle sorting based on combined views.
 *
 * @ViewsSort("views_combine")
 */
class Combine extends SortPluginBase implements CacheableDependencyInterface {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views_combine\ViewsCombiner::setCombineSorts()
   */
  public function query() {
    // Sit, Ubu, Sit. Good dog.
  }

}
