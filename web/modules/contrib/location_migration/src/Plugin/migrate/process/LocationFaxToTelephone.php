<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process plugin that converts D7 location fax number values to D9 telephone.
 *
 * @MigrateProcessPlugin(
 *   id = "location_fax_to_telephone",
 *   handle_multiples = TRUE
 * )
 */
class LocationFaxToTelephone extends LocationProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($lids = $this->getLocationIds($value, $row))) {
      // Empty field.
      return NULL;
    }

    $processed_values = [];
    foreach ($lids as $lid) {
      $processed_values[] = $this->getLocationProperties($lid)['fax'] ?? NULL;
    }

    return $processed_values;
  }

}
