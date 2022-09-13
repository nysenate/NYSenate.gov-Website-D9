<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Processes and returns media internet field values.
 *
 * @MigrateProcessPlugin(
 *   id = "media_internet_field_value",
 *   handle_multiples = TRUE
 * )
 */
class MediaInternetFieldValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // We're operating with the source values.
    $source_values = $row->getSource();
    $replaced = preg_replace([
      '/^youtube:\/\/v\//i',
      '/^vimeo:\/\/v\//i',
    ], [
      'https://www.youtube.com/watch?v=',
      'https://vimeo.com/',
    ], $source_values['uri']);

    if ($replaced !== $source_values['uri']) {
      return $replaced;
    }

    return $value;
  }

}
