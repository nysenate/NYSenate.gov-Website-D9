<?php

/**
 * @file
 * Views hooks for Other View filter module.
 */

use Drupal\Core\Entity\ContentEntityType;
use Drupal\search_api\IndexInterface;

/**
 * Implements hook_views_data_alter().
 */
function other_view_filter_views_data_alter(array &$data) {
  $definitions = \Drupal::entityTypeManager()->getDefinitions();

  foreach ($definitions as $definition) {
    if ($definition instanceof ContentEntityType) {

      $field = $definition->getKey('id');
      $table = $definition->getDataTable();
      $table = $table ? $table : $definition->getBaseTable();

      if ($field && $table && isset($data[$table])) {
        $data[$table]['other_view_filter'] = [
          'title' => t('Other view result'),
          'help' => t('Filters this view against results of selected other view.'),
          'real field' => $field,
          'filter' => [
            'title' => t('Other view result'),
            'help' => t('Filters this view against results of selected other view.'),
            'field' => $field,
            'id' => 'other_views_filter',
          ],
        ];
      }

    }
  }

  if (\Drupal::moduleHandler()->moduleExists('search_api')) {
    $indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();

    foreach ($indexes as $index) {
      if ($index instanceof IndexInterface) {
        $table = 'search_api_index_' . $index->id();

        if (isset($data[$table])) {
          $data[$table]['other_view_filter'] = [
            'title' => t('Other view result'),
            'help' => t('Filters this view against results of selected other view.'),
            'real field' => 'search_api_id',
            'filter' => [
              'title' => t('Other view result'),
              'help' => t('Filters this view against results of selected other view.'),
              'field' => 'search_api_id',
              'id' => 'other_views_filter',
            ],
          ];
        }

      }
    }
  }

  foreach ($data as $table => $definition) {
    if (
      !isset($definition['other_view_filter']) &&
      isset($definition['table']['base'], $definition['table']['base']['field'])
    ) {
      $data[$table]['other_view_filter'] = [
        'title' => t('Other view result'),
        'help' => t('Filters this view against results of selected other view.'),
        'real field' => $definition['table']['base']['field'],
        'filter' => [
          'title' => t('Other view result'),
          'help' => t('Filters this view against results of selected other view.'),
          'field' => $definition['table']['base']['field'],
          'id' => 'other_views_filter',
        ],
      ];
    }
  }

}
