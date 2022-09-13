<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for file entity fields.
 *
 * @MigrateProcessPlugin(
 *   id = "file_entity_field_instance_settings"
 * )
 */
class FileEntityFieldInstanceSettings extends MediaMigrationFieldInstanceSettingsProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $type = $row->getSourceProperty('type');

    if ($type == 'file_entity') {
      $existing_media_type_ids = array_keys($this->getExistingMediaTypeData());
      $predicted_media_type_ids = array_keys($this->getPredictedMediaTypeData());
      $media_type_ids = array_unique(array_merge($existing_media_type_ids, $predicted_media_type_ids));
      $widget_settings = $row->getSourceProperty('widget');
      $target_bundles = !empty($widget_settings['settings']['allowed_types'])
        ? array_filter($widget_settings['settings']['allowed_types'])
        : [];

      // In Drupal 7, when no media types are explicitly enabled on this field,
      // that means that every media type is allowed. In Drupal 8|9, this
      // feature is not available anymore: "target_bundles" cannot be empty.
      $value['handler_settings']['target_bundles'] = !empty($target_bundles)
        ? $target_bundles
        : array_combine($media_type_ids, $media_type_ids);
    }
    return $value;
  }

}
