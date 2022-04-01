<?php

/**
 * @file
 * Views integration for the views_field_view module.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_field_view_views_data_alter(&$data) {
  $data['views']['view']['field'] = [
    'title' => t('View'),
    'help' => t('Embed a view as a field. This can cause slow performance, so enable some caching.'),
    'id' => 'view',
  ];
}
