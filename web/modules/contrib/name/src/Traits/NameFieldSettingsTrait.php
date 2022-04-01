<?php

namespace Drupal\name\Traits;

use Drupal\Core\Form\FormStateInterface;

/**
 * Name settings trait.
 *
 * Used for handling the core field settings.
 */
trait NameFieldSettingsTrait {

  /**
   * Gets the default settings for controlling a name element.
   *
   * @return array
   *   Default settings.
   */
  protected static function getDefaultNameFieldSettings() {
    return [
      'components' => [
        'title' => TRUE,
        'given' => TRUE,
        'middle' => TRUE,
        'family' => TRUE,
        'generational' => TRUE,
        'credentials' => TRUE,
      ],
      'minimum_components' => [
        'title' => FALSE,
        'given' => TRUE,
        'middle' => FALSE,
        'family' => TRUE,
        'generational' => FALSE,
        'credentials' => FALSE,
      ],
      'allow_family_or_given' => FALSE,
      'max_length' => [
        'title' => 31,
        'given' => 63,
        'middle' => 127,
        'family' => 63,
        'generational' => 15,
        'credentials' => 255,
      ],
      'field_type' => [
        'title' => 'select',
        'given' => 'text',
        'middle' => 'text',
        'family' => 'text',
        'generational' => 'select',
        'credentials' => 'text',
      ],
      'autocomplete_source' => [
        'title' => [
          'title',
        ],
        'given' => [],
        'middle' => [],
        'family' => [],
        'generational' => [
          'generation',
        ],
        'credentials' => [],
      ],
      'autocomplete_separator' => [
        'title' => ' ',
        'given' => ' -',
        'middle' => ' -',
        'family' => ' -',
        'generational' => ' ',
        'credentials' => ', ',
      ],
      'title_options' => [
        t('-- --'),
        t('Mr.'),
        t('Mrs.'),
        t('Miss'),
        t('Ms.'),
        t('Dr.'),
        t('Prof.'),
      ],
      'generational_options' => [
        t('-- --'),
        t('Jr.'),
        t('Sr.'),
        t('I'),
        t('II'),
        t('III'),
        t('IV'),
        t('V'),
        t('VI'),
        t('VII'),
        t('VIII'),
        t('IX'),
        t('X'),
      ],
      'sort_options' => [
        'title' => FALSE,
        'given' => FALSE,
        'middle' => FALSE,
        'family' => FALSE,
        'generational' => FALSE,
        'credentials' => FALSE,
      ],
      'component_layout' => 'default',
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
  protected function getDefaultNameFieldSettingsForm(array $settings, array &$form, FormStateInterface $form_state, $has_data = TRUE) {

    $components = _name_translations();
    $field_options = [
      'select' => $this->t('Drop-down'),
      'text' => $this->t('Text field'),
      'autocomplete' => $this->t('Autocomplete'),
    ];

    // @todo: Refactor out for alternative sources.
    $autocomplete_sources_options = [
      'title' => $this->t('Title options'),
      'generational' => $this->t('Generational options'),
    ];

    $element = [];
    $element['components'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Components'),
      '#default_value' => array_keys(array_filter($settings['components'])),
      '#required' => TRUE,
      '#description' => $this->t('Only selected components will be activated on this field. All non-selected components / component settings will be ignored.'),
      '#options' => $components,
    ];
    $element['minimum_components'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Minimum components'),
      '#default_value' => array_keys(array_filter($settings['minimum_components'])),
      '#required' => TRUE,
      '#description' => $this->t('The minimal set of components required before the field is considered completed enough to save.'),
      '#options' => $components,
      '#element_validate' => [[get_class($this), 'validateMinimumComponents']],
    ];
    // Placeholder for additional fields to couple with the components section.
    $element['components_extra'] = [
      '#indent_row' => TRUE,
    ];
    $element['allow_family_or_given'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow a single valid given or family value to fulfill the minimum component requirements for both given and family components.'),
      '#default_value' => !empty($settings['allow_family_or_given']),
      '#table_group' => 'components_extra',
    ];
    $element['field_type'] = [
      '#title' => $this->t('Field type'),
      '#description' => $this->t('The Field type controls how the field is rendered. Autocomplete is a text field with autocomplete, and the behaviour of this is controlled by the field settings.'),
    ];
    $element['max_length'] = [
      '#title' => $this->t('Maximum length'),
      '#description' => $this->t('The maximum length of the field in characters. This must be between 1 and 255.'),
    ];

    $sort_options = is_array($settings['sort_options']) ? $settings['sort_options'] : [
      'title' => 'title',
      'generational' => '',
    ];
    $element['sort_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sort options'),
      '#default_value' => $sort_options,
      '#description' => $this->t("This enables sorting on the options after the vocabulary terms are added and duplicate values are removed."),
      '#options' => _name_translations([
        'title' => '',
        'generational' => '',
      ]),
    ];

    $element['autocomplete_source'] = [
      '#title' => $this->t('Autocomplete sources'),
      '#description' => $this->t('At least one value must be selected before you can enable the autocomplete option on the input textfields.'),
    ];

    $element['autocomplete_separator'] = [
      '#title' => $this->t('Autocomplete separator'),
      '#description' => $this->t('This allows you to override the default handling that the autocomplete uses to handle separations between components. If empty, this defaults to a single space.'),
    ];

    foreach ($components as $key => $title) {
      $min_length = 1;
      $element['max_length'][$key] = [
        '#type' => 'number',
        '#min' => $min_length,
        '#max' => 255,
        '#title' => $this->t('Maximum length for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['max_length'][$key],
        '#required' => TRUE,
        '#size' => 5,
      ];
      $element['autocomplete_source'][$key] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Autocomplete options'),
        '#title_display' => 'invisible',
        '#default_value' => $settings['autocomplete_source'][$key],
        '#options' => $autocomplete_sources_options,
      ];
      if ($key != 'title') {
        unset($element['autocomplete_source'][$key]['#options']['title']);
      }
      if ($key != 'generational') {
        unset($element['autocomplete_source'][$key]['#options']['generational']);
      }
      $element['autocomplete_separator'][$key] = [
        '#type' => 'textfield',
        '#title' => $this->t('Autocomplete separator for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['autocomplete_separator'][$key],
        '#size' => 10,
      ];
      $element['field_type'][$key] = [
        '#type' => 'radios',
        '#title' => $this->t('@title field type', ['@title' => $components['title']]),
        '#title_display' => 'invisible',
        '#default_value' => $settings['field_type'][$key],
        '#required' => TRUE,
        '#options' => $field_options,
      ];

      if (!($key == 'title' || $key == 'generational')) {
        unset($element['field_type'][$key]['#options']['select']);
      }
    }

    // TODO - Grouping & grouping sort
    // TODO - Allow reverse free tagging back into the vocabulary.
    $title_options = implode("\n", array_filter($settings['title_options']));
    $element['title_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('@title options', ['@title' => $components['title']]),
      '#default_value' => $title_options,
      '#required' => TRUE,
      '#description' => $this->t("Enter one @title per line. Prefix a line using '--' to specify a blank value text. For example: '--Please select a @title'.", [
        '@title' => $components['title'],
      ]),
      '#element_validate' => [[get_class($this), 'validateTitleOptions']],
      '#table_group' => 'none',
    ];
    $generational_options = implode("\n", array_filter($settings['generational_options']));
    $element['generational_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('@generational options', ['@generational' => $components['generational']]),
      '#default_value' => $generational_options,
      '#required' => TRUE,
      '#description' => $this->t("Enter one @generational suffix option per line. Prefix a line using '--' to specify a blank value text. For example: '----'.", [
        '@generational' => $components['generational'],
      ]),
      '#element_validate' => [[get_class($this), 'validateGenerationalOptions']],
      '#table_group' => 'none',
    ];
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      // TODO - Make the labels more generic.
      // Generational suffixes may be imported from one or more vocabularies
      // using the tag '[vocabulary:xxx]', where xxx is the vocabulary id.
      // Terms that exceed the maximum length of the generational suffix are
      // not added to the options list.
      $element['title_options']['#description'] .= ' ' . $this->t("%label_plural may be also imported from one or more vocabularies using the tag '[vocabulary:xxx]', where xxx is the vocabulary machine-name or id. Terms that exceed the maximum length of the %label are not added to the options list.", [
        '%label_plural' => $this->t('Titles'),
        '%label' => $this->t('Title'),
      ]);
      $element['generational_options']['#description'] .= ' ' . $this->t("%label_plural may be also imported from one or more vocabularies using the tag '[vocabulary:xxx]', where xxx is the vocabulary machine-name or id. Terms that exceed the maximum length of the %label are not added to the options list.", [
        '%label_plural' => $this->t('Generational suffixes'),
        '%label' => $this->t('Generational suffix'),
      ]);
    }

    $items = [
      $this->t('The order for Asian names is Family Middle Given Title Credentials'),
      $this->t('The order for Eastern names is Title Family Given Middle Credentials'),
      $this->t('The order for German names is Title Credentials Given Middle Surname'),
      $this->t('The order for Western names is Title Given Middle Surname Credentials'),
    ];
    $item_list = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $layout_description = $this->t('<p>This controls the order of the widgets that are displayed in the form.</p>')
      . \Drupal::service('renderer')->render($item_list)
      . $this->t('<p>Note that when you select the Asian and German name formats, the Generational field is hidden and defaults to an empty string.</p>');
    $element['component_layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Language layout'),
      '#default_value' => $this->getSetting('component_layout'),
      '#options' => [
        'default' => $this->t('Western names'),
        'asian' => $this->t('Asian names'),
        'eastern' => $this->t('Eastern names'),
        'german' => $this->t('German names'),
      ],
      '#description' => $layout_description,
      '#table_group' => 'above',
      '#required' => TRUE,
      '#weight' => -49,
    ];

    return $element;
  }

  /**
   * Helper function to validate minimum components.
   *
   * @param array $element
   *   Element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateMinimumComponents(array $element, FormStateInterface $form_state) {
    $minimum_components = $form_state->getValue(['settings', 'minimum_components']);
    $diff = array_intersect(array_keys(array_filter($minimum_components)), ['given', 'family']);
    if (count($diff) == 0) {
      $components = array_intersect_key(_name_translations(), array_flip(['given', 'family']));
      $form_state->setError($element, t('%label must have one of the following components: %components', [
        '%label' => t('Minimum components'),
        '%components' => implode(', ', $components),
      ]));
    }

    $components = $form_state->getValue(['settings', 'components']);
    $minimum_components = $form_state->getValue(['settings', 'minimum_components']);
    $diff = array_diff_key(array_filter($minimum_components), array_filter($components));
    if (count($diff)) {
      $components = array_intersect_key(_name_translations(), $diff);
      $form_state->setError($element, t('%components can not be selected for %label when they are not selected for %label2.', [
        '%label' => t('Minimum components'),
        '%label2' => t('Components'),
        '%components' => implode(', ', $components),
      ]));
    }
  }

  /**
   * Helper function to validate minimum components.
   *
   * @param array $element
   *   Element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateTitleOptions($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);
    $max_length = $form_state->getValue(['settings', 'max_length', 'title']);
    static::validateOptions($element, $form_state, $values, $max_length);
  }

  /**
   * Helper function to validate minimum components.
   *
   * @param array $element
   *   Element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateGenerationalOptions($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value']);
    $max_length_keys = ['settings', 'max_length', 'generational'];
    $max_length = $form_state->getValue($max_length_keys);
    static::validateOptions($element, $form_state, $values, $max_length);
  }

}
