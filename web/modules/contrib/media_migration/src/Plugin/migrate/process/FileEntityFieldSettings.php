<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field settings for file entity fields migrated to media ones.
 *
 * @MigrateProcessPlugin(
 *   id = "file_entity_field_settings"
 * )
 */
class FileEntityFieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') == 'file_entity') {
      $value['target_type'] = 'media';
    }
    return $value;
  }

}
