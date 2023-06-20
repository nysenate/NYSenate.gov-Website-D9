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
 *   id = "senator_user_migrate_map_lookup",
 *   source_module = "nys_migrate"
 * )
 */
class SenatorUserMigrateMapLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Retrieve the senator taxonomy term ID.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Initialize connection.
    $db = Database::getConnection();

    // Query for the Senator taxonomy term referencing the same user account.
    $query = $db->select('taxonomy_term__field_user_account', 'e')
      ->fields('e', ['entity_id']);
    $query->condition('field_user_account_target_id', $value['target_id']);
    $result = $query->execute()->fetchAssoc();

    return $result['entity_id'];
  }

}
