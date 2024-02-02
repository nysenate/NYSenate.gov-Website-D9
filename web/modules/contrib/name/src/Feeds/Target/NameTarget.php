<?php

namespace Drupal\name\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a name field mapper.
 *
 * @FeedsTarget(
 *   id = "name",
 *   field_types = {
 *     "name"
 *   }
 * )
 */
class NameTarget extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition);
    foreach (_name_component_keys() as $key) {
      $target_definition->addProperty($key);
    }
    return $target_definition;
  }

}
