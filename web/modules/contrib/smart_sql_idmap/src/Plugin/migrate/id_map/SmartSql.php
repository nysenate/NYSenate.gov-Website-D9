<?php

namespace Drupal\smart_sql_idmap\Plugin\migrate\id_map;

use Drupal\Component\Plugin\PluginBase;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
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
   * {@inheritdoc}
   *
   * @see https://drupal.org/i/2845340
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher, MigrationPluginManagerInterface $migration_plugin_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $event_dispatcher, $migration_plugin_manager);

    // Default generated table names, limited to 63 characters.
    $machine_name = str_replace(PluginBase::DERIVATIVE_SEPARATOR, '__', $this->migration->id());
    $prefix_length = strlen($this->database->tablePrefix());

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

    $core_major_minor = implode(
      '.',
      [
        explode('.', \Drupal::VERSION)[0],
        explode('.', \Drupal::VERSION)[1],
      ]
    );
    if (version_compare($core_major_minor, '9.3', 'ge')) {
      return $result ?? [];
    }
    // Fix for https://drupal.org/i/3227549 and workaround for
    // https://drupal.org/i/3227660.
    return $result ? $result : ['rollback_action' => 99999];
  }

}
