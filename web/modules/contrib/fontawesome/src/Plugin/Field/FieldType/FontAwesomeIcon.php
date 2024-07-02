<?php

namespace Drupal\fontawesome\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of Font Awesome Icon.
 *
 * @FieldType(
 *   id = "fontawesome_icon",
 *   label = @Translation("Font Awesome Icon"),
 *   module = "fontawesome",
 *   category = @Translation("Icons"),
 *   description = @Translation("A Font Awesome icon"),
 *   default_formatter = "fontawesome_icon_formatter",
 *   default_widget = "fontawesome_icon_widget",
 *   serialized_property_names = {
 *     "settings"
 *   }
 * )
 */
class FontAwesomeIcon extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        'icon_name' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => TRUE,
        ],
        'style' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => TRUE,
        ],
        'settings' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['icon_name'] = DataDefinition::create('string')
      ->setLabel(t('Icon Name'))
      ->setDescription(t('The name of the icon'));
    $properties['style'] = DataDefinition::create('string')
      ->setLabel(t('Icon Style'))
      ->setDescription(t('The style of the icon'));
    $properties['settings'] = DataDefinition::create('string')
      ->setLabel(t('Icon Settings'))
      ->setDescription(t('The additional class settings for the icon'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $icon_name = $this->get('icon_name')->getValue();
    return $icon_name === NULL || $icon_name === '';
  }

}
