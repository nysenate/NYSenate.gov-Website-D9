<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process plugin that converts D7 location email values to D8|D9 email.
 *
 * @MigrateProcessPlugin(
 *   id = "location_contact_to_contact",
 *   handle_multiples = TRUE
 * )
 */
class LocationContactToContact extends LocationProcessPluginBase {

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
      $processed_values[] = $this->getLocationProperties($lid)['name'] ?? NULL;
    }

    return $processed_values;
  }

}
