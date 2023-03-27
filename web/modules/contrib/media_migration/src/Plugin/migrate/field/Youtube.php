<?php

namespace Drupal\media_migration\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field migration plugin for Drupal 7 Youtube fields.
 *
 * @MigrateField(
 *   id = "youtube",
 *   core = {7},
 *   type_map = {
 *    "youtube" = "entity_reference"
 *   },
 *   source_module = "youtube",
 *   destination_module = "media"
 * )
 */
class Youtube extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'youtube_video' => 'entity_reference_entity_view',
      'youtube_thumbnail' => 'media_thumbnail',
      'youtube_url' => 'entity_reference_label',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'youtube' => 'media_library_widget',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'youtube' => [
        'plugin' => 'media_image_field_settings',
        'expected_source_type' => 'youtube',
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
            'migration' => 'd7_youtube_field',
            'source' => 'input',
          ],
        ],
      ]
    );
  }

}
