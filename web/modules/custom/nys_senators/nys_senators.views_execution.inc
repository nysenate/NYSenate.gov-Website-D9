<?php

/**
 * @file
 * Alters views queries as needed.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Implements hook_views_query_alter().
 */
function nys_senators_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() == 'content' || $view->id() == 'my_senator_termlist') {
    if ($query instanceof Sql) {
      $query->addTag('nys_senators_access');
    }
  }
}
