<?php

/**
 * Implements hook_views_data_alter().
 */
function views_send_views_data_alter(&$data) {
  $data['views']['views_send_bulk_form'] = array(
    'title' => t('Send e-mail'),
    'help' => t('Provide a checkbox to select the row for e-mail sending.'),
    'field' => array(
      'id' => 'views_send_bulk_form',
    ),
  );
}