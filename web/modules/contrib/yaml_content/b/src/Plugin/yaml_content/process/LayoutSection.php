<?php

namespace Drupal\yaml_content\Plugin\yaml_content\process;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\Section;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\YamlContentProcessBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for processing layout Section field item.
 *
 * @YamlContentProcess(
 *   id = "section",
 *   title = @Translation("Layout section Processor"),
 *   description = @Translation("Process Section object.")
 * )
 */
class LayoutSection extends YamlContentProcessBase implements ContainerFactoryPluginInterface {

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Constructs a new LayoutSection.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UuidInterface $uuid) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->uuidGenerator = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(ProcessingContext $context, array &$field_data) {
    $layout_section = $this->configuration;
    // Generate uuid for components.
    $components = [];
    foreach ($layout_section['components'] as $index => $component) {
      $uuid = $this->uuidGenerator->generate();
      $component['uuid'] = $uuid;
      $components[$uuid] = $component;
    }
    $layout_section['components'] = $components;
    $field_data['section'] = Section::fromArray($layout_section);
    unset($field_data['#process']);
  }

}
