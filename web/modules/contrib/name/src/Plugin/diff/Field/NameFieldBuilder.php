<?php

namespace Drupal\name\Plugin\diff\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\diff\DiffEntityParser;
use Drupal\name\NameFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin to diff text fields.
 *
 * @FieldDiffBuilder(
 *   id = "name_field_diff_builder",
 *   label = @Translation("Name Field"),
 *   field_types = {
 *     "name"
 *   },
 * )
 */
class NameFieldBuilder extends FieldDiffBuilderBase {

  /**
   * The name formatter.
   *
   * @var \Drupal\name\NameFormatterInterface
   */
  protected $formatter;

  /**
   * Constructs a Name Field diff builder instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\diff\DiffEntityParser $entity_parser
   *   The entity parser.
   * @param \Drupal\name\NameFormatterInterface $formatter
   *   The name formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entity_parser, NameFormatterInterface $formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_parser);
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('diff.entity_parser'),
      $container->get('name.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $items) {
    $result = [];
    if ($this->configuration['compare_format']) {
      foreach ($items as $item) {
        $result[] = (string) $this->formatter->format($item->filteredArray(), $this->configuration['compare_format']);
      }
    }
    else {
      foreach ($items as $item) {
        $output = [];
        $values = $item->toArray();
        foreach ($item->activeComponents() as $key => $label) {
          $output[] = "$label: $values[$key]";
        }
        $result[] = implode("\n", $output);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['compare_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Name format'),
      '#default_value' => $this->configuration['compare_format'],
      '#options' => name_get_custom_format_options(),
      '#empty_option' => $this->t('-- components --'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['compare_format'] = $form_state->getValue('compare_format');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = [
      'format' => '',
    ];
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
