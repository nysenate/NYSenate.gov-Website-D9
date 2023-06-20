<?php

namespace Drupal\nys_list_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\nys_list_formatter\Plugin\ListFormatterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'list_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "list_formatter",
 *   label = @Translation("List"),
 *   field_types = {},
 * )
 */
class ListFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Plugin Interface.
   *
   * @var \Drupal\nys_list_formatter\Plugin\ListFormatterPluginManager
   */
  private $listFormatterManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\nys_list_formatter\Plugin\ListFormatterPluginManager $list_formatter_manager
   *   The list manager plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        $label,
        $view_mode,
        array $third_party_settings,
        ListFormatterPluginManager $list_formatter_manager,
        EntityTypeManagerInterface $entity_type_manager
    ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->listFormatterManager = $list_formatter_manager;
    $this->entityTypeManager = $entity_type_manager;
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
          $configuration['label'],
          $configuration['view_mode'],
          $configuration['third_party_settings'],
          $container->get('plugin.manager.list_formatter'),
          $container->get('entity_type.manager')
      );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings += [
      'type' => 'ul',
      'class' => 'list-formatter-list',
      'comma_full_stop' => 0,
      'comma_and' => 0,
      'comma_tag' => 'div',
      'term_plain' => 0,
      'comma_override' => 0,
      'separator_custom' => '',
      'separator_custom_tag' => 'span',
      'separator_custom_class' => 'list-formatter-separator',
      'list_formatter_contrib' => [],
    ];
    return $settings;
  }

  /**
   * Implements FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    $elements['type'] = [
      '#title' => $this->t('List type'),
      '#type' => 'select',
      '#options' => $this->listTypes(),
      '#default_value' => $this->getSetting('type'),
      '#required' => TRUE,
    ];
    $elements['comma_and'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Include 'and' before the last item"),
      '#default_value' => $this->getSetting('comma_and'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][type]"]' => ['value' => 'comma'],
        ],
      ],
    ];
    $elements['comma_full_stop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Append comma separated list with '.'"),
      '#default_value' => $this->getSetting('comma_full_stop'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][type]"]' => ['value' => 'comma'],
        ],
      ],
    ];

    // Override comma with custom separator.
    $elements['comma_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override comma separator'),
      '#description' => $this->t('Override the default comma separator with a custom separator string.'),
      '#default_value' => $this->getSetting('comma_override'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][type]"]' => ['value' => 'comma'],
        ],
      ],
    ];
    $elements['separator_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom separator'),
      '#description' => $this->t('Override default comma separator with a custom separator string. You must add your own spaces in this string if you want them. @example', ['@example' => "E.g. ' + ', or ' => '"]),
      '#size' => 40,
      '#default_value' => $this->getSetting('separator_custom'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][comma_override]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['separator_custom_tag'] = [
      '#type' => 'select',
      '#title' => $this->t('separator HTML wrapper'),
      '#description' => $this->t('An HTML tag to wrap the separator in.'),
      '#options' => $this->wrapperOptions(),
      '#default_value' => $this->getSetting('separator_custom_tag'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][comma_override]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['separator_custom_class'] = [
      '#title' => $this->t('Separator classes'),
      '#type' => 'textfield',
      '#description' => $this->t('A CSS class to use in the wrapper tag for the separator.'),
      '#default_value' => $this->getSetting('separator_custom_class'),
      '#element_validate' => [[get_class($this), 'validateClasses']],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][comma_override]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['comma_tag'] = [
      '#type' => 'select',
      '#title' => $this->t('HTML wrapper'),
      '#description' => $this->t('An HTML tag to wrap the list in. The CSS class below will be added to this tag.'),
      '#options' => $this->wrapperOptions(),
      '#default_value' => $this->getSetting('comma_tag'),
      '#states' => [
        'visible' => [
          ':input[name=fields[' . $field_name . '][settings_edit_form][settings][type]"]' => ['value' => 'comma'],
        ],
      ],
    ];
    $elements['class'] = [
      '#title' => $this->t('List classes'),
      '#description' => $this->t('A CSS class to use in the markup for the field list.'),
      '#type' => 'textfield',
      '#size' => 40,
      '#default_value' => $this->getSetting('class'),
      '#element_validate' => [[get_class($this), 'validateClasses']],
    ];

    $plugin = $this->getListFormatter();
    $plugin->additionalSettings($elements, $this->fieldDefinition, $this);

    return $elements;
  }

  /**
   * Implements FormatterInterface::settingsSummary().
   */
  public function settingsSummary() {
    $summary = [];

    $types = $this->listTypes();
    $summary[] = $types[$this->getSetting('type')];

    if ($this->getSetting('class')) {
      $summary[] = $this->t('CSS Class: %class', ['%class' => $this->getSetting('class')]);
    }

    if ($this->getSetting('comma_override')) {
      $summary[] = $this->t('*<em>Comma separator overridden</em>*');
    }

    return $summary;
  }

  /**
   * Implements FormatterInterface::viewElements().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $list_items = [];
    $plugin = $this->getListFormatter();

    $list_items = $plugin->createList($items, $this->fieldDefinition, $this, $langcode);

    // If there are no list items, return and render nothing.
    if (empty($list_items)) {
      return [];
    }

    $type = $this->getSetting('type');

    // CSS classes are checked for validity on submission. drupal_attributes()
    // runs each attribute value through check_plain().
    $classes = explode(' ', $this->getSetting('class'));

    switch ($type) {
      case 'ul':
      case 'ol':
        // Render as one element, item list.
        $elements[] = [
          '#theme' => 'item_list',
          '#list_type' => $type,
          '#items' => $list_items,
          '#attributes' => [
            'class' => $classes,
          ],
        ];
        break;

      case 'comma':
        // Render as one element, comma separated list.
        $elements[] = [
          '#theme' => 'list_formatter_comma',
          '#items' => $list_items,
          '#settings' => $this->getSettings(),
          '#attributes' => [
            'class' => $classes,
          ],
        ];
        break;
    }

    return $elements;
  }

  /**
   * Returns a list of available list types.
   *
   * @return array
   *   An options list of types.
   */
  public function listTypes() {
    return [
      'ul' => $this->t('Unordered HTML list (ul)'),
      'ol' => $this->t('Ordered HTML list (ol)'),
      'comma' => $this->t('Comma separated list'),
    ];
  }

  /**
   * Helper method return an array of html tags; formatted for a select list.
   *
   * @return array
   *   A keyed array of available html tags.
   */
  public function wrapperOptions() {
    return [
      '0' => $this->t('No HTML tag'),
      'div' => $this->t('Div'),
      'span' => $this->t('Span'),
      'p' => $this->t('Paragraph'),
      'h1' => $this->t('Header 1'),
      'h2' => $this->t('Header 2'),
      'h3' => $this->t('Header 3'),
      'h4' => $this->t('Header 4'),
      'h5' => $this->t('Header 5'),
      'h6' => $this->t('Header 6'),
    ];
  }

  /**
   * Validate classes.
   *
   * Validate that a space-separated list of values are lowercase and
   * appropriate for use as HTML classes.
   *
   * @see list_formatter_field_formatter_settings_form()
   */
  public static function validateClasses($element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    $classes = explode(' ', $value);
    foreach ($classes as $class) {
      if ($class != Html::getClass($class)) {
        $form_state->setError($element, t('List classes contain illegal characters; classes should be lowercase and may contain letters, numbers, and dashes.'));
        return;
      }
    }
  }

  /**
   * Load the list_formatter plugin instance.
   */
  public function getListFormatter() {
    // In the case of entity_reference fields, the provider is core, so checking
    // the field type in that case.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getType() == 'entity_reference') {
      $provider = $this->fieldDefinition->getFieldStorageDefinition()->getType();
    }
    else {
      $field_id = $this->fieldDefinition->getTargetEntityTypeId() . '.' . $this->fieldDefinition->getName();
      $field_config = $this->entityTypeManager->getStorage('field_storage_config')->load($field_id);
      $provider = $field_config->getTypeProvider();
    }

    $field_type = $this->fieldDefinition->getType();
    $list_formatter_info = $this->listFormatterManager->fieldListInfo(TRUE);
    if (in_array($field_type, $list_formatter_info['field_types'][$provider])) {
      $plugin_type = $provider;
    }
    else {
      $plugin_type = 'default';
    }

    $plugin = $this->listFormatterManager->createInstance($plugin_type);

    return $plugin;
  }

}
