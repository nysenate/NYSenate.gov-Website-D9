<?php

/**
 * @file
 * Alters views queries as needed.
 */

use Drupal\user\Entity\User;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Implements hook_views_query_alter().
 */
function nys_senators_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {

  // Add custom access to content view.
  if ($view->id() == 'content') {
    if ($query instanceof Sql) {
      $query->addTag('nys_senators_access');
    }
  }

  // Add conditions for MCPs to show Senators and Committees.
  if ($view->id() == 'my_senator_termlist') {
    if ($query instanceof Sql) {

      $user = User::load(\Drupal::currentUser()->id());
      $senator_helper = \Drupal::service('nys_senators.senators_helper');

      // Check for MCP.
      if (!$senator_helper->senatorUserIsAdmin($user) &&
        $user->hasField('field_senator_multiref') &&
        !empty($senators = $user->field_senator_multiref->getValue())) {

        // Format a nice array for where conditions.
        $senator_tids = [];

        foreach ($senators as $senator) {
          $senator_tids[] = $senator['target_id'];
        }

        // Add conditions and or them.
        $query->addWhere(999, 'taxonomy_term_field_data.tid', $senator_tids, 'IN');
        $query->addWhere(999, 'taxonomy_term__field_chair.field_chair_target_id', $senator_tids, 'IN');
        $query->setWhereGroup('OR', 999);
      }
    }
  }
}
