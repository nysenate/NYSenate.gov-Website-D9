<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process plugin that converts D7 location field values to D8|D9 address field.
 *
 * @MigrateProcessPlugin(
 *   id = "location_to_address",
 *   handle_multiples = TRUE
 * )
 */
class LocationToAddress extends LocationProcessPluginBase {

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
      $location_data = $this->getLocationProperties($lid);
      $processed_values[] = !empty($location_data)
        ? [
          'locality' => $location_data['city'],
          'organization' => $location_data['name'],
          'address_line1' => $location_data['street'],
          'address_line2' => $location_data['additional'],
          'administrative_area' => $location_data['province'],
          'sorting_code' => '',
          'country_code' => mb_strtoupper($location_data['country']),
          'postal_code' => $location_data['postal_code'],
        ]
        : NULL;
    }

    return $processed_values;
  }

}
