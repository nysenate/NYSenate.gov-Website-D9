<?php

namespace Drupal\media_migration\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Field Plugin for image field to media image field migrations.
 *
 * @MigrateField(
 *   id = "media_image",
 *   core = {7},
 *   type_map = {
 *     "media_image" = "entity_reference",
 *   },
 *   source_module = "image",
 *   destination_module = "media",
 * )
 */
class MediaImage extends MediaMigrationFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'media_image' => [
        'plugin' => 'media_image_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);

    parent::alterFieldMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $settings = [
      'media_image' => [
        'plugin' => 'media_image_field_instance_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);

    parent::alterFieldInstanceMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    // The "media_migration_delta_sort" plugin sorts field values for PostgreSQL
    // sources.
    // @see \Drupal\media_migration\Plugin\migrate\process\MediaMigrationDeltaSort
    // @todo remove when https://drupal.org/i/3164520 is fixed.
    $process = [
      [
        'plugin' => 'media_migration_delta_sort',
        'source' => $field_name,
      ],
    ];

    $process[] = [
      'plugin' => 'sub_process',
      'process' => [
        'target_id' => [
          'plugin' => 'migration_lookup',
          'source' => 'fid',
          'migration' => ['d7_file_entity', 'd7_file_plain'],
          'no_stub' => TRUE,
        ],
      ],
    ];

    $migration->setProcessOfProperty($field_name, $process);

    // Add image media migrations as required dependencies.
    $migration_dependencies = $migration->getMigrationDependencies();
    $migration_dependencies['required'] = array_unique(
      array_merge(
        array_values($migration_dependencies['required']),
        ['d7_file_plain:image', 'd7_file_entity:image']
      )
    );
    $migration->set('migration_dependencies', $migration_dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    $mapping = [];
    if (
      $this->moduleHandler->moduleExists('media_library') &&
      $this->fieldWidgetManager->hasDefinition('media_library_widget')
    ) {
      $mapping = [
        'media_generic' => 'media_library_widget',
        'image_image' => 'media_library_widget',
      ];
    }
    return $mapping + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'file_download_link' => 'entity_reference_label',
      'file_audio' => 'entity_reference_entity_view',
      'file_video' => 'entity_reference_entity_view',
      'file_default' => 'entity_reference_entity_view',
      'file_table' => 'entity_reference_entity_view',
      'file_url_plain' => 'entity_reference_label',
      'file_image_picture' => 'entity_reference_entity_view',
      'file_image_image' => 'entity_reference_entity_view',
      'file_rendered' => 'entity_reference_entity_view',
      'image' => 'entity_reference_entity_view',
      'picture' => 'entity_reference_entity_view',
      'picture_sizes_formatter' => 'entity_reference_entity_view',
    ] + parent::getFieldFormatterMap();
  }

}
