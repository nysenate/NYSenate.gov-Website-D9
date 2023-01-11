<?php

namespace Drupal\location_migration\Plugin\migrate\source;

/**
 * Drupal 7 geolocation field storage source for D7 location entity data.
 *
 * @MigrateSource(
 *   id = "d7_entity_location_field",
 *   core = {7},
 *   source_module = "location"
 * )
 */
class EntityLocationFieldStorage extends EntityLocationFieldInstance {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $iterator = parent::initializeIterator();

    $rows = [];
    foreach ($iterator as $iterator_item) {
      unset($iterator_item['bundle']);
      $entity_type = $iterator_item['entity_type'];
      $field_name = $iterator_item['field_name'];
      $iterator_item_id = "{$entity_type}:{$field_name}";
      if (!array_key_exists($iterator_item_id, $rows)) {
        $rows[$iterator_item_id] = $iterator_item;
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    unset($fields['bundle']);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $identifiers = parent::getIds();
    unset($identifiers['bundle']);
    return $identifiers;
  }

}
