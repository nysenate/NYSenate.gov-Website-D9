<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * The process plugin for getting the senator term id.
 *
 * @MigrateProcessPlugin(
 *   id = "senator_migrate_map_lookup",
 *   source_module = "nys_migrate"
 * )
 */
class SenatorMigrateMapLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Retrieve the senator name.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $table_name = 'migrate_map_' . $this->configuration['migration'];

    // Initialize connection.
    $db = Database::getConnection();

    $query = $db->select($table_name, 's')
      ->fields('s', ['destid1']);
    $query->condition('s.sourceid1', $value['target_id']);
    $result = $query->execute()->fetchAssoc();

    return $result['destid1'] ?? NULL;
  }

}
