<?php

/**
 * @file
 * Provide views data.
 */

/**
 * Implements hook_views_data().
 */
function views_field_compare_views_data() {

  $data['views']['field_comparison'] = [
   'title' => t('Field comparison'),
    'help' => t('Compare two database field values.'),
    'filter' => [
      'id' => 'field_comparison',
    ],
  ];

  $data['views']['field_contained'] = [
    'title' => t('Field contained'),
    'help' => t('Database field value contained in another multi-valued database field.'),
    'filter' => [
      'id' => 'field_contained',
    ],
  ];

  return $data;
}
