<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process plugin that converts D7 location "www" values to D8|D9 link.
 *
 * @MigrateProcessPlugin(
 *   id = "location_www_to_link",
 *   handle_multiples = TRUE
 * )
 */
class LocationWwwToLink extends LocationProcessPluginBase {

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
      $link_value = $this->getLocationProperties($lid)['www'] ?? NULL;
      $processed_values[] = !empty($link_value)
        ? ['uri' => $link_value]
        : NULL;
    }

    return $processed_values;
  }

}
