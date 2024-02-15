<?php

namespace Drupal\rabbit_hole\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define 'Rabbit hole' field type.
 *
 * @FieldType(
 *   id = "rabbit_hole",
 *   label = @Translation("Rabbit hole"),
 *   default_widget = "rabbit_hole",
 *   cardinality = 1,
 *   no_ui = TRUE
 * )
 */
class RabbitHole extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['action'] = DataDefinition::create('string')
      ->setLabel(t('Behavior'))
      ->setRequired(TRUE);
    $properties['settings'] = DataDefinition::create('any')
      ->setLabel(t('Settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'action';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'action' => [
          'type' => 'text',
          'not null' => TRUE,
        ],
        'settings' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('action')->getValue();
    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $actions = \Drupal::service('plugin.manager.rabbit_hole_behavior_plugin')->getBehaviors();
    $values['action'] = array_rand($actions);
    return $values;
  }

}
