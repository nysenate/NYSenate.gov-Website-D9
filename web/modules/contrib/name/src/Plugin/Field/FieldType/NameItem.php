<?php

namespace Drupal\name\Plugin\Field\FieldType;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\name\Traits\NameFieldSettingsTrait;
use Drupal\name\Traits\NameFormDisplaySettingsTrait;
use Drupal\name\Traits\NameFormSettingsHelperTrait;
use Drupal\name\Traits\NameAdditionalPreferredTrait;

/**
 * Plugin implementation of the 'name' field type.
 *
 * Majority of the settings handling is delegated to the traits so that these
 * can be reused.
 *
 * @FieldType(
 *   id = "name",
 *   label = @Translation("Name"),
 *   description = @Translation("Stores real name."),
 *   default_widget = "name_default",
 *   default_formatter = "name_default"
 * )
 */
class NameItem extends FieldItemBase implements TrustedCallbackInterface {

  use NameFieldSettingsTrait;
  use NameFormDisplaySettingsTrait;
  use NameFormSettingsHelperTrait;
  use NameAdditionalPreferredTrait;

  /**
   * Definition of name field components.
   *
   * @var array
   */
  protected static $components = [
    'title',
    'given',
    'middle',
    'family',
    'generational',
    'credentials',
  ];

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [];
    foreach (static::$components as $key) {
      $columns[$key] = [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
      ];
    }
    return [
      'columns' => $columns,
      'indexes' => [
        'given' => ['given'],
        'family' => ['family'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = self::getDefaultNameFieldSettings();
    $settings += self::getDefaultNameFormDisplaySettings();
    $settings += self::getDefaultAdditionalPreferredSettings();
    $settings['override_format'] = 'default';
    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'));

    $properties['given'] = DataDefinition::create('string')
      ->setLabel(t('Given'));

    $properties['middle'] = DataDefinition::create('string')
      ->setLabel(t('Middle name(s)'));

    $properties['family'] = DataDefinition::create('string')
      ->setLabel(t('Family'));

    $properties['generational'] = DataDefinition::create('string')
      ->setLabel(t('Generational'));

    $properties['credentials'] = DataDefinition::create('string')
      ->setLabel(t('Credentials'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // There is no main property for this field item.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->properties as $property) {
      $definition = $property->getDataDefinition();
      if (!$definition->isComputed() && $property->getValue() !== NULL) {
        return FALSE;
      }
    }
    if (isset($this->values)) {
      foreach ($this->values as $name => $value) {
        // Title & generational have no meaning by themselves.
        if ($name == 'title' || $name == 'generational') {
          continue;
        }
        if (isset($value) && strlen($value) && !isset($this->properties[$name])) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Returns active components only.
   *
   * @return array
   *   Array of filtered name component values.
   */
  public function filteredArray() {
    $values = [];
    $field = $this->getFieldDefinition();
    $settings = $field->getSettings();
    $active_components = array_filter($settings['components']);
    foreach ($this->getProperties() as $name => $property) {
      if (isset($active_components[$name]) && $active_components[$name]) {
        $values[$name] = $property->getValue();
      }
    }
    return $values;
  }

  /**
   * Get a list of active components.
   *
   * @return array
   *   Keyed array of active component labels.
   */
  public function activeComponents() {
    $settings = $this->getFieldDefinition()->getSettings();
    $components = [];
    foreach (_name_translations() as $key => $label) {
      if (!empty($settings['components'][$key])) {
        $components[$key] = empty($settings['labels'][$key]) ? $label : $settings['labels'][$key];
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $element = $this->getDefaultNameFieldSettingsForm($settings, $form, $form_state);
    $element += $this->getDefaultNameFormDisplaySettingsForm($settings, $form, $form_state);
    foreach ($this->getNameAdditionalPreferredSettingsForm($form, $form_state) as $key => $value) {
      $element[$key] = $value;
      $element[$key]['#table_group'] = 'none';
      $element[$key]['#weight'] = 50;
    }

    $element['#pre_render'][] = [$this, 'fieldSettingsFormPreRender'];

    // Add the overwrite user name option.
    if ($this->getFieldDefinition()->getTargetEntityTypeId() == 'user') {

      $preferred_field = \Drupal::config('name.settings')
        ->get('user_preferred');

      $element['name_user_preferred'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Use this field to override the user's login name?"),
        '#description' => $this->t('You may need to clear the @cache_link before this change is seen everywhere.', [
          '@cache_link' => Link::fromTextAndUrl('Performance cache', Url::fromRoute('system.performance_settings'))->toString(),
        ]),
        '#default_value' => (($preferred_field == $this->getFieldDefinition()->getName()) ? 1 : 0),
        '#table_group' => 'above',
        '#weight' => -100,
      ];

      // Store the machine name of the Name field.
      $element['name_user_preferred_fieldname'] = [
        '#type' => 'hidden',
        '#default_value' => $this->getFieldDefinition()->getName(),
        '#table_group' => 'above',
        '#weight' => -99,
      ];

      $element['override_format'] = [
        '#type' => 'select',
        '#title' => $this->t('User name override format to use'),
        '#default_value' => $this->getSetting('override_format'),
        '#options' => name_get_custom_format_options(),
        '#table_group' => 'above',
        '#weight' => -98,
      ];

      $element['#element_validate'] = [[get_class($this), 'validateUserPreferred']];
    }
    else {
      // We may extend this feature to Profile2 latter.
      $element['override_format'] = [
        '#type' => 'value',
        '#value' => $this->getSetting('override_format'),
        '#table_group' => 'none',
      ];
    }

    return $element;
  }

  /**
   * Manage whether the name field should override a user's login name.
   */
  public static function validateUserPreferred(&$element, FormStateInterface $form_state, &$complete_form) {

    $value = NULL;
    $config = \Drupal::configFactory()->getEditable('name.settings');

    // Ensure the name field value should override a user's login name.
    if ((!empty($element['name_user_preferred'])) && ($element['name_user_preferred']['#value'] == 1)) {
      // Retrieve the name field's machine name.
      $value = $element['name_user_preferred_fieldname']['#default_value'];
    }

    // Ensure that the login-name-override configuration has changed.
    if ($config->get('user_preferred') != $value) {

      // Update the configuration with the new value.
      $config->set('user_preferred', $value)->save();

      // Retrieve the ID of all existing users.
      $query = \Drupal::entityQuery('user');
      $uids = $query->execute();

      foreach ($uids as $uid) {
        // Invalidate the cache for each user so that
        // the appropriate login name will be displayed.
        Cache::invalidateTags(['user:' . $uid]);
      }

      \Drupal::logger('name')->notice('Cache cleared for data tagged as %tag.', ['%tag' => 'user:{$uid}']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Single reused generation of 100 random names.
    $names = &drupal_static(__FUNCTION__, []);
    if (empty($names)) {
      $names = \Drupal::service('name.generator')->generateSampleNames(100, $field_definition);
    }
    return $names[array_rand($names)];
  }

}
