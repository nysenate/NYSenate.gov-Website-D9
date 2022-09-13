<?php

namespace Drupal\name\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\name\NameOptionsProvider;
use Drupal\name\Traits\NameFormDisplaySettingsTrait;
use Drupal\name\Traits\NameFormSettingsHelperTrait;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Plugin implementation of the 'name' widget.
 *
 * @FieldWidget(
 *   id = "name_default",
 *   module = "name",
 *   label = @Translation("Name components"),
 *   field_types = {
 *     "name"
 *   }
 * )
 */
class NameWidget extends WidgetBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  use NameFormDisplaySettingsTrait;
  use NameFormSettingsHelperTrait;

  /**
   * Name options provider service.
   *
   * @var \Drupal\name\NameOptionsProvider
   */
  protected $optionsProvider;

  /**
   * Constructs a NameWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\name\NameOptionsProvider $options_provider
   *   Name options provider service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, NameOptionsProvider $options_provider) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->optionsProvider = $options_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('name.options_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget_settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    if (!empty($widget_settings['override_field_settings'])
        && !$this->isDefaultValueWidget($form_state)) {
      $settings = $widget_settings + $field_settings;
    }
    else {
      $settings = $field_settings;
    }

    $element += [
      '#type' => 'name',
      '#title' => $this->fieldDefinition->getLabel(),
      '#components' => [],
      '#minimum_components' => array_filter($settings['minimum_components']),
      '#allow_family_or_given' => !empty($settings['allow_family_or_given']),
      '#default_value' => isset($items[$delta]) ? $items[$delta]->getValue() : NULL,
      '#field' => $this,
      '#credentials_inline' => empty($settings['credentials_inline']) ? 0 : 1,
      '#widget_layout' => empty($settings['widget_layout']) ? 'stacked' : $settings['widget_layout'],
      '#component_layout' => empty($settings['component_layout']) ? 'default' : $settings['component_layout'],
      '#show_component_required_marker' => !empty($settings['show_component_required_marker']),
    ];

    $components = array_filter($settings['components']);
    foreach (_name_translations() as $key => $title) {
      if (isset($components[$key])) {
        $element['#components'][$key]['type'] = 'textfield';

        $size = !empty($settings['size'][$key]) ? $settings['size'][$key] : 60;
        $title_display = isset($settings['title_display'][$key]) ? $settings['title_display'][$key] : 'description';

        $element['#components'][$key]['title'] = Html::escape($settings['labels'][$key]);
        $element['#components'][$key]['title_display'] = $title_display;

        $element['#components'][$key]['size'] = $size;
        $element['#components'][$key]['maxlength'] = !empty($settings['max_length'][$key]) ? $settings['max_length'][$key] : 255;

        // Provides backwards compatibility with Drupal 6 modules.
        $field_type = ($key == 'title' || $key == 'generational') ? 'select' : 'text';
        $field_type = isset($settings['field_type'][$key])
            ? $settings['field_type'][$key]
            : (isset($settings[$key . '_field']) ? $settings[$key . '_field'] : $field_type);

        if ($field_type == 'select') {
          $element['#components'][$key]['type'] = 'select';
          $element['#components'][$key]['size'] = 1;
          $element['#components'][$key]['options'] = $this->optionsProvider->getOptions($this->fieldDefinition, $key);
        }
        elseif ($field_type == 'autocomplete') {
          if ($sources = $settings['autocomplete_source'][$key]) {
            $sources = array_filter($sources);
            if (!empty($sources)) {
              $element['#components'][$key]['autocomplete'] = [
                '#autocomplete_route_name' => 'name.autocomplete',
                '#autocomplete_route_parameters' => [
                  'field_name' => $this->fieldDefinition->getName(),
                  'entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
                  'bundle' => $this->fieldDefinition->getTargetBundle(),
                  'component' => $key,
                ],
              ];
            }
          }
        }
      }
      else {
        $element['#components'][$key]['exclude'] = TRUE;
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    $new_values = [];
    foreach ($values as $item) {
      $value = implode('', array_intersect_key($item, _name_translations()));
      if (strlen($value)) {
        $new_values[] = $item;
      }
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = self::getDefaultNameFormDisplaySettings();
    $settings['override_field_settings'] = FALSE;
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['override_field_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override shared field settings'),
      '#default_value' => $this->getSetting('override_field_settings'),
      '#table_group' => 'above',
      '#weight' => -100,
    ];

    $element += $this->getDefaultNameFormDisplaySettingsForm($settings, $form, $form_state);

    // Remove inaccessible name components as defined in the field settings.
    $field_settings = $this->getFieldSettings();
    $components = array_keys(array_filter($field_settings['components']));
    $components = array_combine($components, $components);
    $element['#excluded_components'] = array_diff_key(_name_translations(), $components);
    $element['#pre_render'][] = [$this, 'fieldSettingsFormPreRender'];
    $element['widget_layout']['#states'] = [
      'visible' => [
        ':input[name$="[override_field_settings]"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    $element['name_settings']['#states'] = $element['widget_layout']['#states'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $widget_settings = $this->getSettings();
    if (empty($widget_settings['override_field_settings'])) {
      array_unshift($summary, $this->t('Using shared settings'));
    }
    else {
      array_unshift($summary, $this->t('Overridden settings'));
    }

    return $summary;
  }

}
