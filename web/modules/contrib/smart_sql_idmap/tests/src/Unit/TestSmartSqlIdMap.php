<?php

namespace Drupal\Tests\smart_sql_idmap\Unit;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\smart_sql_idmap\Plugin\migrate\id_map\SmartSql;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Smart SQL ID map-based plugin that can be used for testing SmartSql.
 */
class TestSmartSqlIdMap extends SmartSql implements \Iterator {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a TestSqlIdMap object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID for the migration process to do.
   * @param mixed $plugin_definition
   *   The configuration for the plugin.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to do.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(Connection $database, MigrationPluginManagerInterface $migration_plugin_manager, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher) {
    $this->database = $database;
    $this->migrationPluginManager = $migration_plugin_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $event_dispatcher, $migration_plugin_manager);
  }

  /**
   * {@inheritdoc}
   */
  public $message;

  /**
   * {@inheritdoc}
   *
   * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  public function getMigrationPluginManager() {
    // phpcs:enable
    return parent::getMigrationPluginManager();
  }

  /**
   * Gets the field schema.
   *
   * @param array $id_definition
   *   An array defining the field, with a key 'type'.
   *
   * @return array
   *   A field schema depending on value of key 'type'.  An empty array is
   *   returned if 'type' is not defined.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getFieldSchema(array $id_definition) {
    if (!isset($id_definition['type'])) {
      return [];
    }
    switch ($id_definition['type']) {
      case 'integer':
        return [
          'type' => 'int',
          'not null' => TRUE,
        ];

      case 'string':
        return [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ];

      default:
        throw new MigrateException($id_definition['type'] . ' not supported');
    }
  }

}
