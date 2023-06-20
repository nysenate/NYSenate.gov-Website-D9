<?php

/**
 * @file
 * Alters views queries as needed.
 */

use Drupal\block_content\Entity\BlockContent;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;

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
      if (!$senator_helper->senatorUserIsAdmin($user)
            && $user->hasField('field_senator_multiref')
            && !empty($senators = $user->field_senator_multiref->getValue())
        ) {

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

  if ($view->id() == 'global_promo_banner'
        && $view->current_display == 'senator_microsite_interior_footer'
    ) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    if (!empty($query->where[0]['conditions'][0]['value'][':node_field_data_nid1'])) {
      $nid = $query->where[0]['conditions'][0]['value'][':node_field_data_nid1'];

      $banner_ids = [];
      $node = $node_storage->load($nid);

      // Get Senator's Landing Page.
      if ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) {
        $senator_id = $node->get('field_senator_multiref')->first()->getString() ?? '';
        if (!empty($senator_id)) {
          $microsite_home = $node_storage->loadByProperties(
                [
                  'type' => 'microsite_page',
                // Landing page.
                  'field_microsite_page_type' => '200005',
                  'field_senator_multiref' => $senator_id,
                ]
            );

          $microsite_home = reset($microsite_home);

          // Get Promotional Banners.
          if ($microsite_home->hasField('field_block') && !$microsite_home->get('field_block')->isEmpty()) {
            foreach ($microsite_home->get('field_block') as $key => $value) {
              /**
               * @var \Drupal\block_content\Entity\BlockContent $block_content
               */
              $block_content = $value->entity ?? NULL;

              if ($block_content instanceof BlockContent) {
                if ($block_content->bundle() == 'promotional_banners_list') {
                  if ($block_content->hasField('field_promotional_banners') && !$block_content->get('field_promotional_banners')->isEmpty()) {
                    foreach ($block_content->get('field_promotional_banners') as $banner) {
                      $banner_ids[] = $banner->target_id ?? '';
                    }
                  }
                }
              }
            }
          }
        }
      }

      // Add an empty string is there are no banner_ids.
      $banner_ids = $banner_ids == [] ? [''] : $banner_ids;

      // Alter query.
      $query->where[0]['conditions'][0]['field'] = 'node_field_data.nid';
      $query->where[0]['conditions'][0]['value'] = $banner_ids;
      $query->where[0]['conditions'][0]['operator'] = 'IN';
    }
  }
}
