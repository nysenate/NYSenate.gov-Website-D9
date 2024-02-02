<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field settings for media image fields.
 *
 * @MigrateProcessPlugin(
 *   id = "media_image_field_settings"
 * )
 */
class MediaImageFieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source_type = $this->configuration['expected_source_type'] ?? 'media_image';

    if ($row->getSourceProperty('type') == $source_type) {
      $value['target_type'] = 'media';
    }
    return $value;
  }

}
