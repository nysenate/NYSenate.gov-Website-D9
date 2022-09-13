<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for sorting multi-value field values' by their delta.
 *
 * No configuration available.
 *
 * This plugin is required only because Drupal core's
 * FieldableEntity::getFieldValues() does not guarantee that the returned
 * field values are sorted by their delta, so when the source Drupal database
 * is PostgreSQL, the values of some multi-value fields are migrated in reversed
 * order.
 *
 * @todo remove when https://drupal.org/i/3164520 is fixed for all supported
 *   Drupal core release.
 *
 * @MigrateProcessPlugin(
 *   id = "media_migration_delta_sort",
 *   handle_multiples = TRUE
 * )
 */
class MediaMigrationDeltaSort extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = (array) $value;
    ksort($value);
    return $value;
  }

}
