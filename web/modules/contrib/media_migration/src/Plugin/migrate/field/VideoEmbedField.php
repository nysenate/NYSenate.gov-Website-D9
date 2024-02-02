<?php

namespace Drupal\media_migration\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field migration plugin for Drupal 7 Video embed field.
 *
 * @MigrateField(
 *   id = "videoembedfield",
 *   core = {7},
 *   type_map = {
 *    "video_embed_field" = "entity_reference"
 *   },
 *   source_module = "video_embed_field",
 *   destination_module = "media"
 * )
 */
class VideoEmbedField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'video_embed_field' => 'entity_reference_entity_view',
      'video_embed_field_url' => 'entity_reference_label',
      'video_embed_field_thumbnail' => 'media_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'video_embed_field_video' => 'media_library_widget',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'video_embed_field' => [
        'plugin' => 'media_image_field_settings',
        'expected_source_type' => 'video_embed_field',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);

    parent::alterFieldMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $migration->setProcessOfProperty(
      $field_name,
      [
        'plugin' => 'sub_process',
        'source' => $field_name,
        'process' => [
          'target_id' => [
            'plugin' => 'migration_lookup',
            'migration' => 'd7_video_embed_field',
            'source' => 'video_url',
          ],
        ],
      ]
    );
  }

}
