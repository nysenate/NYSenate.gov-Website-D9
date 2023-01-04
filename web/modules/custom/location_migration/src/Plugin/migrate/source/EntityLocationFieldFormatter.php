<?php

namespace Drupal\location_migration\Plugin\migrate\source;

/**
 * Drupal 7 geolocation field formatter source for D7 location entity data.
 *
 * @MigrateSource(
 *   id = "d7_entity_location_field_formatter",
 *   core = {7},
 *   source_module = "location"
 * )
 */
class EntityLocationFieldFormatter extends EntityLocationFieldInstance {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = [];
    foreach (parent::initializeIterator() as $parent_iterator_row) {
      $view_mode_configs = array_filter($parent_iterator_row['location_settings']['display'], function ($key) {
        return !in_array($key, ['hide', 'weight']);
      }, ARRAY_FILTER_USE_KEY);
      // Taxonomy term and user entity location configuration does not store
      // view mode configuration.
      $view_mode_configs += ['full' => 1];
      foreach ($view_mode_configs as $view_mode => $display) {
        $base = ['view_mode' => $view_mode === 'full' ? 'default' : $view_mode];
        // When location wasn't enabled for this view mode, it means that every
        // new field should be hidden.
        if (!$display) {
          $base['display_hidden'] = TRUE;
        }
        $rows[] = $base + $parent_iterator_row;
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'view_mode' => $this->t('The view mode'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return parent::getIds() + [
      'view_mode' => [
        'type' => 'string',
        'alias' => 'elfci',
      ],
    ];
  }

}
