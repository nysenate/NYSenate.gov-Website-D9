<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Looks up the District Term ID for a given d7 node.
 *
 * @MigrateProcessPlugin(
 *   id = "get_district_id",
 *   source_module = "nys_migrate"
 * )
 */
class GetDistrictId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Value should be the nid of the original node.
    $value = trim($value);
    if (empty($value)) {
      return;
    }

    // Establish a connection to the migration db.
    $nys7_db = Database::getConnection('default', 'migrate');
    // Query for the original District value.
    // Making an assumption it is a single value.
    $district_results = $nys7_db->select('field_data_field_district', 'd')
      ->fields('d', ['field_district_target_id'])
      ->condition('entity_id', $value)
      ->execute()->fetchCol();

    if (!$district_results) {
      throw new MigrateSkipRowException(sprintf('No district found for school %s', $value));
    }

    // Making no assumptions on Districts id mapping.
    $db = \Drupal::database();
    $result = $db->select('migrate_map_nys_senate_gov_taxonomy_term_districts', 'm')
      ->fields('m', ['destid1'])
      ->condition('sourceid1', $district_results, 'IN')
      ->execute()->fetchCol();

    if (!$result) {
      throw new MigrateSkipRowException(sprintf('Mapping id for District %s not found', $value));
    }

    return reset($result);
  }

}
