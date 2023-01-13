<?php

namespace Drupal\location_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\location_migration\LocationMigration;
use Drupal\location_migration\Plugin\migrate\DestinationFieldTrait;
use Drupal\location_migration\Plugin\migrate\process\LocationToAddressFieldInstanceSettings;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 7 geolocation field instance source for D7 location entity data.
 *
 * @MigrateSource(
 *   id = "d7_entity_location_field_instance",
 *   core = {7},
 *   source_module = "location"
 * )
 */
class EntityLocationFieldInstance extends EntityLocationFieldInstanceBase implements ContainerFactoryPluginInterface {

  use DestinationFieldTrait;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Constructs an entity location field source plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration plugin instance.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_manager) {
    $configuration += [
      'entity_type' => NULL,
      'bundle' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->fieldTypePluginManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ] = $this->configuration;
    $subquery = NULL;

    if (
      $this->moduleExists('location_node') &&
      (!$entity_type || $entity_type === 'node') &&
      $this->getDatabase()->schema()->tableExists('node_type')
    ) {
      $subquery = $this->select('node_type', 'nt');
      $subquery->addExpression("'node'", 'entity_type');
      $subquery->addField('nt', 'type', 'bundle');
      $subquery->addExpression("CONCAT('location_settings_node_', nt.type)", 'variable_name');

      if ($bundle) {
        $subquery->condition('nt.type', $bundle);
      }
    }

    if (
      $this->moduleExists('location_taxonomy') &&
      (!$entity_type || $entity_type === 'taxonomy_term') &&
      $this->getDatabase()->schema()->tableExists('taxonomy_vocabulary')
    ) {
      $union_query = $this->select('taxonomy_vocabulary', 'tv');
      $union_query->addExpression("'taxonomy_term'", 'entity_type');
      $union_query->addField('tv', 'machine_name', 'bundle');
      $union_query->addExpression("CONCAT('location_taxonomy_', tv.vid)", 'variable_name');

      if ($bundle) {
        $union_query->condition('tv.machine_name', $bundle);
      }

      $this->addUnionQuery($subquery, $union_query);
    }

    if (
      $this->moduleExists('location_user') &&
      (!$entity_type || $entity_type === 'user') &&
      $this->variableGet('location_settings_user', FALSE)
    ) {
      $union_query = $this->select('variable', 'uv')
        ->condition('uv.name', 'location_settings_user');
      $union_query->addExpression("'user'", 'entity_type');
      $union_query->addExpression("'user'", 'bundle');
      $union_query->addExpression("'location_settings_user'", 'variable_name');

      $this->addUnionQuery($subquery, $union_query);
    }

    if ($subquery instanceof SelectInterface) {
      $query = $this->select('variable', 'v');
      $query->join($subquery, 'els', 'v.name = els.variable_name');
      $query->addField('els', 'entity_type', 'entity_type');
      $query->addField('els', 'bundle', 'bundle');
      $query->addField('v', 'value', 'data');
      return $query;
    }

    // When we don't have to create additional field related configurations for
    // locations stored directly for nodes, taxonomy terms or users, we return
    // a query which's result is zero rows.
    return $this->select('system', 'system')
      ->fields('system')
      ->condition('system.name', 'location')
      ->condition('system.status', 3333);
  }

  /**
   * Performs a query union.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface|null $destination
   *   The "destination" query which should be extended.
   * @param \Drupal\Core\Database\Query\SelectInterface $source
   *   The query which should be added.
   */
  protected static function addUnionQuery(&$destination, SelectInterface $source) {
    if ($destination instanceof SelectInterface) {
      $destination->union($source);
      return;
    }

    $destination = clone $source;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();

    // Add the array of all instances using the same base field to each row.
    $rows = [];
    foreach ($results as $result) {
      $entity_type_id = $result['entity_type'];
      // Let's assume that the destination entity type ID is the same as the
      // source.
      if (!($entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id, FALSE))) {
        continue;
      }
      $field_label_args = [
        '@entity-label' => $entity_type_definition->getSingularLabel(),
      ];
      $settings = unserialize($result['data']);
      if ($settings['multiple']['max'] === '0') {
        continue;
      }
      $result['location_settings'] = $settings;
      $result['cardinality'] = (int) $settings['multiple']['max'];
      $result['widget_weight'] = (int) ($settings['form']['weight'] ?? 0);
      $result['formatter_weight'] = (int) ($settings['display']['weight'] ?? 0);
      $result['field_name'] = LocationMigration::getEntityLocationFieldBaseName($entity_type_id, $result['cardinality']);
      $address_display_is_hidden = empty(array_diff([
        'name',
        'street',
        'additional',
        'city',
        'province',
        'postal_code',
        'country',
      ], static::getDisplayHiddenFields($settings)));
      $address_widget_is_hidden = empty(array_diff([
        'name',
        'street',
        'additional',
        'city',
        'province',
        'postal_code',
        'country',
      ], static::getFormHiddenFields($settings)));

      // This module depends on Address module, so we assume that the "address"
      // field type is available.
      $rows[] = [
        'type' => 'address',
        'widget_type' => 'address_default',
        'formatter_type' => 'address_default',
        'field_label' => (string) $this->t('@field-label of @entity-label', $field_label_args + [
          '@field-label' => LocationMigration::ADDRESS_FIELD_LABEL_PREFIX,
        ]),
        'field_instance_settings' => LocationToAddressFieldInstanceSettings::defaultSettings(),
        'display_hidden' => $address_display_is_hidden,
        'widget_hidden' => $address_widget_is_hidden,
      ] + $result;

      // Add additional extra fields.
      $rows = array_merge(
        $rows,
        $this->getExtraFieldRows($result)
      );
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('The field name.'),
      'entity_type' => $this->t('The entity type.'),
      'bundle' => $this->t('The entity bundle.'),
      'data' => $this->t('The field instance data.'),
      'type' => $this->t('The field type'),
      'cardinality' => $this->t('Cardinality'),
      'translatable' => $this->t('Translatable'),
      'widget_type' => $this->t('The field widget plugin ID.'),
      'formatter_type' => $this->t('The field formatter plugin ID.'),
      'field_label' => $this->t('The label of the field.'),
      'display_hidden' => $this->t('The field should be hidden on view display.'),
      'widget_hidden' => $this->t('The widget should be hidden on the entity form.'),
      'field_instance_settings' => $this->t('Field instance configuration.'),
      'field_formatter_settings' => $this->t('Field formatter configuration.'),
      'field_widget_settings' => $this->t('Field widget configuration.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
        'alias' => 'elfci',
      ],
      'bundle' => [
        'type' => 'string',
        'alias' => 'elfci',
      ],
      'field_name' => [
        'type' => 'string',
        'alias' => 'elfci',
      ],
    ];
  }

}
