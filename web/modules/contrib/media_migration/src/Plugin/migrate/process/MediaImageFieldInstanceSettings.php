<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for media image fields.
 *
 * @MigrateProcessPlugin(
 *   id = "media_image_field_instance_settings"
 * )
 */
class MediaImageFieldInstanceSettings extends MediaMigrationFieldInstanceSettingsProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') == 'media_image') {
      $field_definition = $row->getSource()['field_definition'] ?? [];
      $field_definition += ['data' => serialize('')];
      $field_storage_data = unserialize($field_definition['data']) ?: [];
      $uri_scheme = !empty($field_storage_data['settings']['uri_scheme'])
        ? $field_storage_data['settings']['uri_scheme']
        : NULL;
      $existing_media_type_data = $this->getExistingMediaTypeData();
      $predicted_media_type_data = $this->getPredictedMediaTypeData();
      $image_media_type_ids = array_reduce(array_merge($predicted_media_type_data, $existing_media_type_data), function (array $carry, array $data) use ($uri_scheme) {
        $source_plugin_id = $data['source_plugin_id'] ?? NULL;
        $media_type_id = $data['bundle'] ?? NULL;
        $scheme = $data['scheme'] ?? NULL;
        if (
          $media_type_id &&
          $source_plugin_id === 'image' &&
          ($scheme === NULL || ($scheme === $uri_scheme))
        ) {
          $carry[$media_type_id] = $media_type_id;
        }
        return $carry;
      }, ['image' => 'image']);

      $value['handler_settings']['target_bundles'] = array_combine($image_media_type_ids, $image_media_type_ids);
    }
    return $value;
  }

}
