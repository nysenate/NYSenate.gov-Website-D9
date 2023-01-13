<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process plugin that converts D7 location phone values to D8|D9 telephone.
 *
 * @MigrateProcessPlugin(
 *   id = "location_phone_to_telephone",
 *   handle_multiples = TRUE
 * )
 */
class LocationPhoneToTelephone extends LocationProcessPluginBase {

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
      $processed_values[] = $this->getLocationProperties($lid)['phone'] ?? NULL;
    }

    return $processed_values;
  }

}
