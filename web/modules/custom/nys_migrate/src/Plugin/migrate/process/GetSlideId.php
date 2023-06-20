<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Looks up the Revision ID for a given block_content id.
 *
 * @MigrateProcessPlugin(
 *   id = "get_slide_id"
 * )
 */
class GetSlideId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (empty($value)) {
      return;
    }

    // Grab delta of value from overall field.
    $index = array_search($value, $row->getSourceProperty('field_pg_slider_images'));

    // Combine with entity_id to get unique value we assigned to slides.
    $slide_id = $row->getSourceProperty('item_id') . '-' . $index;

    return [
      'value' => $slide_id,
    ];
  }

}
