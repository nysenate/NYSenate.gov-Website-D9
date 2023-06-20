<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * The process plugin for getting the senator name.
 *
 * @MigrateProcessPlugin(
 *   id = "senator_name",
 *   source_module = "nys_migrate"
 * )
 */
class SenatorName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Retrieve the senator name.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $table_name = 'field_data_' . $this->configuration['field'];
    $column_name = $this->configuration['field'] . '_value';

    // Switch to migrate database.
    Database::setActiveConnection('migrate');

    // Initialize connection.
    $db = Database::getConnection();

    $query = $db->select($table_name, 'n')
      ->fields('n', [$column_name]);
    $query->condition('n.bundle', 'senator');
    $query->condition('n.entity_id', $value);

    $result = $query->execute()->fetchAssoc();

    // Switch back.
    Database::setActiveConnection();

    return $result[$column_name];
  }

}
