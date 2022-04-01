<?php

namespace Drupal\name\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\name\NameFormatParser;
use Drupal\name\NameGeneratorInterface;
use Drupal\name\Traits\NameAdditionalPreferredTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\name\NameFormatter as NameFormatterService;

/**
 * Plugin implementation of the 'name' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "name_default",
 *   module = "name",
 *   label = @Translation("Name formatter"),
 *   field_types = {
 *     "name",
 *   }
 * )
 */
class NameFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use NameAdditionalPreferredTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field renderer for any additional components.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The name formatter.
   *
   * @var \Drupal\name\NameFormatter
   */
  protected $formatter;

  /**
   * The name format parser.
   *
   * Directly called to format the examples without the fallback.
   *
   * @var \Drupal\name\NameFormatParser
   */
  protected $parser;

  /**
   * The name generator.
   *
   * @var \Drupal\name\NameGeneratorInterface
   */
  protected $generator;

  /**
   * Constructs a NameFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The rendering service.
   * @param \Drupal\name\NameFormatter $formatter
   *   The name formatter.
   * @param \Drupal\name\NameFormatParser $parser
   *   The name format parser.
   * @param \Drupal\name\NameGeneratorInterface $generator
   *   The name format parser.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFieldManager $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer, NameFormatterService $formatter, NameFormatParser $parser, NameGeneratorInterface $generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
    $this->formatter = $formatter;
    $this->parser = $parser;
    $this->generator = $generator;
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
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('name.formatter'),
      $container->get('name.format_parser'),
      $container->get('name.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings += [
      "format" => "default",
      "markup" => "none",
      "list_format" => "",
      "link_target" => "",
    ];
    $settings += self::getDefaultAdditionalPreferredSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Name format'),
      '#default_value' => $this->getSetting('format'),
      '#options' => name_get_custom_format_options(),
      '#required' => TRUE,
    ];

    $elements['list_format'] = [
      '#type' => 'select',
      '#title' => $this->t('List format'),
      '#default_value' => $this->getSetting('list_format'),
      '#empty_option' => $this->t('-- individually --'),
      '#options' => name_get_custom_list_format_options(),
    ];

    $elements['markup'] = [
      '#type' => 'select',
      '#title' => $this->t('Markup'),
      '#default_value' => $this->getSetting('markup'),
      '#options' => $this->parser->getMarkupOptions(),
      '#description' => $this->t('This option wraps the individual components of the name in SPAN elements with corresponding classes to the component.'),
      '#required' => TRUE,
    ];
    if (!empty($this->fieldDefinition->getTargetBundle())) {
      $elements['link_target'] = [
        '#type' => 'select',
        '#title' => $this->t('Link Target'),
        '#default_value' => $this->getSetting('link_target'),
        '#empty_option' => $this->t('-- no link --'),
        '#options' => $this->getLinkableTargets(),
      ];

      $elements += $this->getNameAdditionalPreferredSettingsForm($form, $form_state);
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];

    // Name format.
    $machine_name = isset($settings['format']) ? $settings['format'] : 'default';
    $name_format = $this->entityTypeManager->getStorage('name_format')->load($machine_name);
    if ($name_format) {
      $summary[] = $this->t('Format: @format (@machine_name)', [
        '@format' => $name_format->label(),
        '@machine_name' => $name_format->id(),
      ]);
    }
    else {
      $summary[] = $this->t('Format: <strong>Missing format.</strong><br/>This field will be displayed using the Default format.');
    }

    // List format.
    if (!isset($settings['list_format']) || $settings['list_format'] == '') {
      $summary[] = $this->t('List format: Individually');
    }
    else {
      $machine_name = isset($settings['list_format']) ? $settings['list_format'] : 'default';
      $name_format = $this->entityTypeManager->getStorage('name_list_format')->load($machine_name);
      if ($name_format) {
        $summary[] = $this->t('List format: @format (@machine_name)', [
          '@format' => $name_format->label(),
          '@machine_name' => $name_format->id(),
        ]);
      }
      else {
        $summary[] = $this->t('List format: <strong>Missing list format.</strong><br/>This field will be displayed using the Default list format.');
      }
    }

    // Additional options.
    $markup_options = $this->parser->getMarkupOptions();
    $summary[] = $this->t('Markup: @type', [
      '@type' => $markup_options[$this->getSetting('markup')],
    ]);

    if (!empty($settings['link_target'])) {
      $targets = $this->getLinkableTargets();
      $summary[] = $this->t('Link: @target', [
        '@target' => empty($targets[$settings['link_target']]) ? $this->t('-- invalid --') : $targets[$settings['link_target']],
      ]);
    }

    $this->settingsNameAdditionalPreferredSummary($summary);

    // Provide an example of the selected format.
    if ($name_format) {
      $names = $this->generator->loadSampleValues(1, $this->fieldDefinition);
      if ($name = reset($names)) {
        $formatted = $this->parser->parse($name, $name_format->get('pattern'));
        if (empty($formatted)) {
          $summary[] = $this->t('Example: <em>&lt;&lt;empty&gt;&gt;</em>');
        }
        else {
          $summary[] = $this->t('Example: @example', [
            '@example' => $formatted,
          ]);
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->count()) {
      return $elements;
    }

    $settings = $this->settings;

    $format = isset($settings['format']) ? $settings['format'] : 'default';
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple() && $items->count() > 1;
    $list_format = $is_multiple && !empty($settings['list_format']) ? $settings['list_format'] : '';

    $extra = $this->parseAdditionalComponents($items);
    $extra['url'] = empty($settings['link_target']) ? NULL : $this->getLinkableTargetUrl($items);

    $item_array = [];
    foreach ($items as $item) {
      $components = $item->toArray() + $extra;
      $item_array[] = $components;
    }

    $this->formatter->setSetting('markup', $this->getSetting('markup'));

    if ($list_format) {
      $elements[0]['#markup'] = $this->formatter->formatList($item_array, $format, $list_format, $langcode);
    }
    else {
      foreach ($item_array as $delta => $item) {
        $elements[$delta]['#markup'] = $this->formatter->format($item, $format, $langcode);
      }
    }

    return $elements;
  }

  /**
   * Determines with markup should be added to the results.
   *
   * @return bool
   *   Returns TRUE if markup should be applied.
   */
  protected function useMarkup() {
    return $this->settings['markup'];
  }

  /**
   * Find any linkable targets.
   *
   * @return array
   *   An array of possible targets.
   */
  protected function getLinkableTargets() {
    $targets = ['_self' => $this->t('Entity URL')];
    $bundle = $this->fieldDefinition->getTargetBundle();
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field) {
      if (!$field->getFieldStorageDefinition()->isBaseField()) {
        switch ($field->getType()) {
          case 'entity_reference':
          case 'link':
            $targets[$field->getName()] = $field->getLabel();
            break;
        }
      }
    }
    return $targets;
  }

  /**
   * Gets the URL object.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The name formatters FieldItemList.
   *
   * @return \Drupal\Core\Url
   *   Returns a Url object.
   */
  protected function getLinkableTargetUrl(FieldItemListInterface $items) {
    try {
      $parent = $items->getEntity();
      if ($this->settings['link_target'] == '_self') {
        if (!$parent->isNew() && $parent->access('view')) {
          return $parent->toUrl();
        }
      }
      elseif ($parent->hasField($this->settings['link_target'])) {
        $target_items = $parent->get($this->settings['link_target']);
        if (!$target_items->isEmpty()) {
          $field = $target_items->getFieldDefinition();
          switch ($field->getType()) {
            case 'entity_reference':
              foreach ($target_items as $item) {
                if (!empty($item->entity) && !$item->entity->isNew() && $item->entity->access('view')) {
                  return $item->entity->toUrl();
                }
              }
              break;

            case 'link':
              foreach ($target_items as $item) {
                if ($url = $item->getUrl()) {
                  return $url;
                }
              }
              break;

          }
        }
      }
    }
    catch (UndefinedLinkTemplateException $e) {
    }

    return Url::fromRoute('<none>');
  }

  /**
   * Gets any additional linked components.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The name formatters FieldItemList.
   *
   * @return array
   *   An array of any additional components if set.
   */
  protected function parseAdditionalComponents(FieldItemListInterface $items) {
    $extra = [];
    foreach (['preferred', 'alternative'] as $key) {
      $key_value = $this->getSetting($key . '_field_reference');
      $sep_value = $this->getSetting($key . '_field_reference_separator');
      if (!$key_value) {
        $key_value = $this->fieldDefinition->getSetting($key . '_field_reference');
        $sep_value = $this->fieldDefinition->getSetting($key . '_field_reference_separator');
      }
      if ($value = name_get_additional_component($this->entityTypeManager, $this->renderer, $items, $key_value, $sep_value)) {
        $extra[$key] = $value;
      }
    }

    return $extra;
  }

}
