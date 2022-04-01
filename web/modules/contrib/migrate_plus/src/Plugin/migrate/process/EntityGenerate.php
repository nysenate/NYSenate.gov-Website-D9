<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin generates entities within the process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_generate"
 * )
 *
 * @see EntityLookup
 *
 * All the configuration from the lookup plugin applies here. In its most
 * simple form, this plugin needs no configuration. If there are fields on the
 * generated entity that are required or need some value, their values can be
 * provided via values and/or default_values configuration options.
 *
 * Example usage with values and default_values configuration:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   type:
 *     plugin: default_value
 *     default_value: page
 *   foo: bar
 *   field_tags:
 *     plugin: entity_generate
 *     source: tags
 *     default_values:
 *       description: Default description
 *     values:
 *       field_long_description: some_source_field
 *       field_foo: '@foo'
 * @endcode
 */
class EntityGenerate extends EntityLookup {

  /**
   * The row from the source to process.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * The migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface
   */
  protected $migrateExecutable;

  /**
   * The MigratePluginManager instance.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $processPluginManager;

  /**
   * The get process plugin instance.
   *
   * @var \Drupal\migrate\Plugin\migrate\process\Get
   */
  protected $getProcessPlugin;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    $instance = parent::create($container, $configuration, $pluginId, $pluginDefinition, $migration);
    $instance->processPluginManager = $container->get('plugin.manager.migrate.process');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $this->row = $row;
    $this->migrateExecutable = $migrateExecutable;
    // Creates an entity if the lookup determines it doesn't exist.
    if (!($result = parent::transform($value, $migrateExecutable, $row, $destinationProperty))) {
      $result = $this->generateEntity($value);
    }

    return $result;
  }

  /**
   * Generates an entity for a given value.
   *
   * @param string $value
   *   Value to use in creation of the entity.
   *
   * @return int|string
   *   The entity id of the generated entity.
   */
  protected function generateEntity($value) {
    if (!empty($value)) {
      $entity = $this->entityTypeManager
        ->getStorage($this->lookupEntityType)
        ->create($this->entity($value));
      $entity->save();

      return $entity->id();
    }
  }

  /**
   * Fabricate an entity.
   *
   * This is intended to be extended by implementing classes to provide for more
   * dynamic default values, rather than just static ones.
   *
   * @param mixed $value
   *   Primary value to use in creation of the entity.
   *
   * @return array
   *   Entity value array.
   */
  protected function entity($value) {
    $entity_values = [$this->lookupValueKey => $value];

    if ($this->lookupBundleKey) {
      $entity_values[$this->lookupBundleKey] = $this->lookupBundle;
    }

    // Gather any static default values for properties/fields.
    if (isset($this->configuration['default_values']) && is_array($this->configuration['default_values'])) {
      foreach ($this->configuration['default_values'] as $key => $value) {
        $entity_values[$key] = $value;
      }
    }
    // Gather any additional properties/fields.
    if (isset($this->configuration['values']) && is_array($this->configuration['values'])) {
      foreach ($this->configuration['values'] as $key => $property) {
        $source_value = $this->row->get($property);
        NestedArray::setValue($entity_values, explode(Row::PROPERTY_SEPARATOR, $key), $source_value, TRUE);
      }
    }

    return $entity_values;
  }

}
