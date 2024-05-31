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
    $source_value = $row->getSourceProperty('uri') ?? $value;
    $replaced = preg_replace([
      '/^youtube:\/\/v\//i',
      '/^vimeo:\/\/v\//i',
      '/^https:\/\/www\.youtube\.com\/watch\?v=(.{11}).*/',
    ], [
      'https://www.youtube.com/watch?v=',
      'https://vimeo.com/',
      'https://www.youtube.com/watch?v=$1',
    ], $value);

    if ($replaced !== $source_value) {
      return $replaced;
    }

    return $value;
  }

}
