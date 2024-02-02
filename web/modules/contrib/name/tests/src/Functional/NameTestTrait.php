<?php

namespace Drupal\Tests\name\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Common testing traits.
 */
trait NameTestTrait {

  /**
   * Creates a name field with default settings.
   *
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param array $storage_extras
   *   Additional field storage settings.
   *   - cardinality (int)
   *   - settings (array)
   * @param array $field_extras
   *   Additional field settings.
   *   - widget: ['type' => 'options_buttons']
   *   - settings (array)
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function createNameField($field_name, $entity_type, $bundle, array $storage_extras = [], array $field_extras = []) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'name',
    ] + $storage_extras)->save();

    $field_config = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'name',
      'bundle' => $bundle,
    ] + $field_extras);
    $field_config->save();
    return $field_config;
  }

  /**
   * Forms an associative array from a linear array.
   *
   * @param array $values
   *   The arrays to combine.
   *
   * @return array
   *   The combined array.
   */
  public function mapAssoc(array $values) {
    return array_combine($values, $values);
  }

}
