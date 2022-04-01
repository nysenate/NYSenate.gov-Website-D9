<?php

namespace Drupal\yaml_content\Plugin\yaml_content\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\YamlContentProcessBase;
use Drupal\yaml_content\Plugin\YamlContentProcessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for querying and loading a referenced entity.
 *
 * @YamlContentProcess(
 *   id = "reference",
 *   title = @Translation("Entity Reference Processor"),
 *   description = @Translation("Attach an entity reference.")
 * )
 */
class Reference extends YamlContentProcessBase implements YamlContentProcessInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityView.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(ProcessingContext $context, array &$field_data) {
    $entity_type = $this->configuration[0];
    $filter_params = $this->configuration[1];

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Use query factory to create a query object for the node of entity_type.
    $query = $entity_storage->getQuery('AND');

    // Apply filter parameters.
    foreach ($filter_params as $property => $value) {
      $query->condition($property, $value);
    }

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      $entity = $entity_storage->create($filter_params);
      $entity_ids = [$entity->id()];
    }

    if (!empty($entity_ids)) {
      // By default reference fields use "target_id" as the destination value
      // in the field structure to store the referenced ID. Some field types
      // use different strings, e.g. og_membership entities use "value". Allow
      // the target value to be changed by passing a third item to the reference
      // configuration.
      $target = 'target_id';
      if (!empty($this->configuration[2])) {
        print_r($this->configuration[2]);
        $target = $this->configuration[2];
      }

      // Use the first match for our value.
      $field_data[$target] = array_shift($entity_ids);

      // Remove process data to avoid issues when setting the value.
      unset($field_data['#process']);

      return $entity_ids;
    }
    $this->throwParamError('Unable to find referenced content', $entity_type, $filter_params);
  }

}
