<?php

namespace Drupal\name\Traits;

use Drupal\Core\Form\FormStateInterface;

/**
 * Name form display settings trait.
 *
 * General form display settings.
 */
trait NameFormDisplaySettingsTrait {

  /**
   * Gets the default settings for controlling a name element.
   *
   * @return array
   *   Default settings.
   */
  protected static function getDefaultNameFormDisplaySettings() {
    return [
      'labels' => [
        'title' => t('Title'),
        'given' => t('Given'),
        'middle' => t('Middle name(s)'),
        'family' => t('Family'),
        'generational' => t('Generational'),
        'credentials' => t('Credentials'),
      ],
      'size' => [
        'title' => 6,
        'given' => 20,
        'middle' => 20,
        'family' => 20,
        'generational' => 5,
        'credentials' => 35,
      ],
      'title_display' => [
        'title' => 'description',
        'given' => 'description',
        'middle' => 'description',
        'family' => 'description',
        'generational' => 'description',
        'credentials' => 'description',
      ],
      'widget_layout' => 'stacked',
      'show_component_required_marker' => FALSE,
      'credentials_inline' => FALSE,
    ];
  }

  /**
   * Returns a form for the default settings defined above.
   *
   * The following keys are closely tied to the pre-render function to theme
   * the settings into a nicer table.
   * - #indent_row: Adds an empty TD cell and adds an 'elements' child that
   *   contains the children (if given).
   * - #table_group: Used to either position within the table by the element
   *   key, or set to 'none', to append it below the table.
   *
   * Any element within the table should have component keyed children.
   *
   * Other elements are rendered directly.
   *
   * @param array $settings
   *   The settings.
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   * @param bool $has_data
   *   A flag to indicate if the field has data.
   *
   * @return array
   *   The form definition for the field settings.
   */
  protected function getDefaultNameFormDisplaySettingsForm(array $settings, array &$form, FormStateInterface $form_state, $has_data = TRUE) {

    $components = _name_translations();

    $title_display_options = [
      'title' => $this->t('above'),
      'description' => $this->t('below'),
      'placeholder' => $this->t('placeholder'),
      'attribute' => $this->t('attribute'),
      'none' => $this->t('hidden'),
    ];

    $element = [];

    // Placeholder for additional fields to couple with the components section.
    $element['components_extra'] = [
      '#indent_row' => TRUE,
    ];

    $element['show_component_required_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show component required marker'),
      '#default_value' => $this->getSetting('show_component_required_marker'),
      '#description' => $this->t('Appends an asterisk after the component title if the component is required as part of a complete name.'),
      '#table_group' => 'components_extra',
    ];
    $element['labels'] = [
      '#title' => $this->t('Labels'),
      '#description' => $this->t('The labels are used to distinguish the components.'),
    ];
    $element['title_display'] = [
      '#title' => $this->t('Label display'),
      '#description' => $this->t('The title display controls how the label of the name component is displayed in the form:<br>"%above" is the standard title;<br>"%below" is the standard description;<br>"%placeholder" uses the placeholder attribute, select lists do not support this option;<br>"%attribute" adds a title attribute to create a tooltip rather than a label.<br>"%hidden" removes the label.', [
        '%above' => t('above'),
        '%below' => t('below'),
        '%placeholder' => t('placeholder'),
        '%attribute' => t('attribute'),
        '%hidden' => t('hidden'),
      ]),
    ];
    $element['size'] = [
      '#title' => $this->t('HTML size'),
      '#description' => $this->t('The HTML size property tells the browser what the width of the field should be when it is rendered. This gets overriden by the themes CSS properties. This must be between 1 and 255.'),
    ];

    $element['credentials_inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the credentials inline'),
      '#default_value' => $this->getSetting('credentials_inline'),
      '#description' => $this->t('The default position is to show the credentials on a line by themselves. This option overrides this to render the component inline.'),
      '#table_group' => 'components_extra',
    ];

    foreach ($components as $key => $title) {
      $element['labels'][$key] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['labels'][$key],
        '#required' => TRUE,
        '#size' => 10,
      ];
      $element['size'][$key] = [
        '#type' => 'number',
        '#min' => 1,
        '#max' => 255,
        '#title' => $this->t('HTML size property for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['size'][$key],
        '#required' => FALSE,
        '#size' => 10,
      ];

      $element['title_display'][$key] = [
        '#type' => 'radios',
        '#title' => $this->t('Label display for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['title_display'][$key],
        '#options' => $title_display_options,
      ];
    }

    $widget_layout_options = [];
    foreach (name_widget_layouts() as $layout => $info) {
      $widget_layout_options[$layout] = $info['label'];
    }
    $element['widget_layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget layout'),
      '#default_value' => $this->getSetting('widget_layout'),
      '#options' => $widget_layout_options,
      '#table_group' => 'above',
      '#required' => TRUE,
      '#weight' => -50,
    ];

    return $element;
  }

}
