<?php

namespace Drupal\media_migration\Plugin\migrate\field;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Field Plugin for file_entity to media migrations.
 *
 * @MigrateField(
 *   id = "file_entity",
 *   core = {7},
 *   type_map = {
 *     "file_entity" = "entity_reference",
 *   },
 *   source_module = "file",
 *   destination_module = "media",
 * )
 */
class FileEntity extends MediaMigrationFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_settings',
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
      'file_entity' => [
        'plugin' => 'file_entity_field_instance_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);

    // @todo In Drupal 7, when no media types are explicitly enabled on this
    // field, that means that every media type is allowed. For handling these
    // cases we have to make this migration depend on the media type migrations.
    // @see \Drupal\media_migration\Plugin\migrate\process\FileEntityFieldInstanceSettings::transform()
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

    // Add the needed media migrations as required dependencies.
    $this->addRequiredMediaMigrationDependencies($migration, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_formatter_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('options/settings', $settings);

    parent::alterFieldFormatterMigration($migration);
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
        'file_generic' => 'media_library_widget',
        'media_generic' => 'media_library_widget',
      ];
    }
    return $mapping + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'file_image_picture' => 'media_responsive_thumbnail',
      'file_image_image' => 'media_thumbnail',
      'file_rendered' => 'entity_reference_entity_view',
      'file_download_link' => 'entity_reference_label',
      'file_audio' => 'entity_reference_entity_view',
      'file_video' => 'entity_reference_entity_view',
      'file_default' => 'entity_reference_entity_view',
      'file_table' => 'entity_reference_entity_view',
      'file_url_plain' => 'entity_reference_label',
    ] + parent::getFieldFormatterMap();
  }

  /**
   * Discovers the file type used in the field and adds their migration deps.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The actual fieldable entity migration where the field belongs to.
   * @param array $field_data
   *   The data (settings) of the field instance to check.
   */
  protected function addRequiredMediaMigrationDependencies(MigrationInterface $migration, array $field_data): void {
    $source_plugin = $migration->getSourcePlugin();

    if (!$source_plugin instanceof DrupalSqlBase) {
      // If the source database is not a legacy Drupal database, we cannot do
      // anything.
      return;
    }

    $source_db = $source_plugin->getDatabase();
    $used_file_types = NULL;

    // Let's check whether we have a type column in the file_managed table.
    $type_column_exists = $source_db->schema()->fieldExists('file_managed', 'type');
    if ($type_column_exists) {
      $field_name = $field_data['field_name'];
      $file_types_query = $source_db->select("field_revision_{$field_name}", 'frd')
        ->distinct()
        ->fields('fm', ['type'])
        ->condition('frd.entity_type', $field_data['entity_type'])
        ->condition('frd.bundle', $field_data['bundle'])
        ->condition('fm.status', TRUE)
        ->condition('fm.uri', 'temporary://%', 'NOT LIKE');
      $file_types_query->innerJoin('file_managed', 'fm', "fm.fid = frd.{$field_name}_fid");
      try {
        $used_file_types = $file_types_query->execute()->fetchAllKeyed(0, 0);
      }
      catch (DatabaseExceptionWrapper $exception) {
      }
    }

    $extra_dependencies = [];
    if (is_null($used_file_types) || in_array('undefined', $used_file_types)) {
      $extra_dependencies[] = 'd7_file_plain';
    }

    if ($type_column_exists && !empty($used_file_types)) {
      foreach ($used_file_types as $type) {
        if ($type === 'undefined') {
          continue;
        }

        $extra_dependencies[] = "d7_file_entity:{$type}";
      }
    }

    // No additional dependencies are required for migrating the given field's
    // values.
    if (empty($extra_dependencies)) {
      return;
    }

    $migration_dependencies = $migration->getMigrationDependencies();
    $migration_dependencies['required'] = array_unique(
      array_merge(
        array_values($migration_dependencies['required']),
        $extra_dependencies
      )
    );
    $migration->set('migration_dependencies', $migration_dependencies);
  }

}
