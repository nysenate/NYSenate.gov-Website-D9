<?php

namespace Drupal\smart_sql_idmap\Plugin\migrate\id_map;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseException;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A smart, sql based ID map.
 *
 * @todo Provide an upgrade path when https://drupal.org/i/2845340 gets fixed.
 *
 * @PluginID("smart_sql")
 */
class SmartSql extends Sql {

  /**
   * Destination IDs index name (or prefix).
   *
   * @const string
   */
  const DESTINATION_INDEX = 'destination';

  /**
   * Source row status index name.
   *
   * @const string
   */
  const ROW_STATUS_INDEX = 'row_status';

  /**
   * {@inheritdoc}
   *
   * @see https://drupal.org/i/2845340
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher, MigrationPluginManagerInterface $migration_plugin_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $event_dispatcher, $migration_plugin_manager);

    // Default generated table names, limited to 63 characters.
    $machine_name = str_replace(PluginBase::DERIVATIVE_SEPARATOR, '__', $this->migration->id());
    $prefix = version_compare(static::getCoreMajorMinor(), '10.1', 'ge')
      ? $this->database->getPrefix()
      : $this->database->tablePrefix();
    $prefix_length = strlen($prefix);

    $map_table_name = 'm_map_' . mb_strtolower($machine_name);
    $this->mapTableName = mb_substr($map_table_name, 0, 63 - $prefix_length) === $map_table_name
      ? $map_table_name
      : mb_substr($map_table_name, 0, 45 - $prefix_length) . '_' . substr(md5($machine_name), 0, 17);

    $message_table_name = 'm_message_' . mb_strtolower($machine_name);
    $this->messageTableName = mb_substr($message_table_name, 0, 63 - $prefix_length) === $message_table_name
      ? $message_table_name
      : mb_substr($message_table_name, 0, 45 - $prefix_length) . '_' . substr(md5($machine_name), 0, 17);
  }

  /**
   * Create the map and message tables if they don't already exist.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  protected function ensureTables(): void {
    parent::ensureTables();
    $this->ensureDestinationIndex();
    $this->ensureStatusIndex();
  }

  /**
   * Ensures that source row status index exists.
   */
  public function ensureStatusIndex(): void {
    $db_schema = $this->database->schema();
    if ($db_schema->indexExists($this->mapTableName, self::ROW_STATUS_INDEX)) {
      return;
    }

    $db_schema->addIndex(
      $this->mapTableName,
      self::ROW_STATUS_INDEX,
      ['source_row_status'],
      [
        'fields' => [
          'source_row_status' => [
            'type' => 'int',
            'size' => 'tiny',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => MigrateIdMapInterface::STATUS_IMPORTED,
            'description' => 'Indicates current status of the source row',
          ],
        ],
      ]
    );
  }

  /**
   * Ensures that destination ID index exists.
   */
  public function ensureDestinationIndex(): void {
    $db_schema = $this->database->schema();
    if ($db_schema->indexExists($this->mapTableName, self::DESTINATION_INDEX)) {
      return;
    }
    // Assume table already exists.
    $count = 1;
    $index_fields = [];
    foreach ($this->migration->getDestinationPlugin()->getIds() as $id_definition) {
      $map_key = 'destid' . $count++;
      $index_fields[$map_key] = $this->getFieldSchema($id_definition);
      $index_fields[$map_key]['not null'] = TRUE;
    }

    // To keep within the MySQL maximum key length of 3072 bytes we try
    // different groupings of destination IDs. Groups are created in chunks
    // starting at a chunk size equivalent to the number of the destination IDs.
    // On each loop the chunk size is reduced by one until either the index is
    // successfully created or the chunk_size is less than zero. If there are no
    // destination IDs left no index is created.
    $chunk_size = count($index_fields);
    while ($chunk_size >= 0) {
      $indexes = [];
      if ($chunk_size > 0) {
        foreach (array_chunk(array_keys($index_fields), $chunk_size) as $key => $index_columns) {
          $index_name = ($key === 0)
            ? static::DESTINATION_INDEX
            : static::DESTINATION_INDEX . $key;
          $indexes[$index_name] = $index_columns;
        }
      }

      try {
        foreach ($indexes as $index_name => $index_columns) {
          $db_schema->addIndex(
            $this->mapTableName,
            $index_name,
            $index_columns,
            ['fields' => $index_fields]
          );
        }
        break;
      }
      catch (DatabaseException $e) {
        foreach (array_keys($indexes) as $index_name) {
          if (!$db_schema->indexExists($this->mapTableName, $index_name)) {
            continue;
          }
          $db_schema->dropIndex($this->mapTableName, $index_name);
        }

        $pdo_exception = $e->getPrevious();
        $mysql_index_error = $pdo_exception instanceof \PDOException && $pdo_exception->getCode() === '42000' && $pdo_exception->errorInfo[1] === 1071;
        // Rethrow the exception if not a mysql index error.
        if (!$mysql_index_error) {
          throw $e;
        }
        $chunk_size--;
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see https://drupal.org/i/3227549
   * @see https://drupal.org/i/3227660
   */
  public function getRowByDestination(array $destination_id_values) {
    $missing_destination_keys = array_diff(
      array_keys($this->destinationIdFields()),
      array_keys($destination_id_values)
    );
    // Fix for https://drupal.org/i/3227549.
    $result = $missing_destination_keys
      ? NULL
      : parent::getRowByDestination($destination_id_values);

    if (version_compare(static::getCoreMajorMinor(), '9.3', 'ge')) {
      return $result ?? [];
    }
    // Fix for https://drupal.org/i/3227549 and workaround for
    // https://drupal.org/i/3227660.
    return $result ?: ['rollback_action' => 99999];
  }

  /**
   * Returns the MAJOR.MINOR Drupal core version.
   *
   * @return string
   *   Drupal core version string with only major and minor numbers.
   */
  protected static function getCoreMajorMinor(): string {
    $pieces = explode('.', \Drupal::VERSION);
    return implode('.', [$pieces[0], $pieces[1]]);
  }

}
