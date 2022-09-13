<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\media_migration\MediaMigration;
use Drupal\migrate_plus\Entity\MigrationInterface as MigrationEntityInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForNonMediaSourceTrait;

/**
 * Tests Migrate Upgrade compatibility and verifies usage steps of README.
 *
 * @group media_migration
 */
class DrushWithMigrateUpgradeFromFileTest extends DrushTestBase {

  use MediaMigrationAssertionsForNonMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_upgrade',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'media_migration') . '/tests/fixtures/drupal7_nomedia.php';
  }

  /**
   * Tests migrations with Migrate Upgrade, Drush and Migrate Tools.
   */
  public function testMigrationWithDrush() {
    // Execute the migrate upgrade drush command from the README.
    // @code
    // drush migrate:upgrade\
    //   --configure-only\
    //   --legacy-db-key=[db-key-of-source-site]\
    //   --legacy-root=[path-to-source-site]
    // @endcode
    $this->drush('migrate:upgrade', ['--configure-only'], [
      'legacy-db-key' => $this->sourceDatabase->getKey(),
      'legacy-root' => drupal_get_path('module', 'media_migration') . '/tests/fixtures',
    ]);

    $migrations = $this->container->get('entity_type.manager')
      ->getStorage('migration')
      ->loadMultiple();

    // "D7_field" migration should depend on file entity type image because the
    // "image" media type has an additional number field.
    $this->assertD7FieldMigration($migrations['upgrade_d7_field']);

    // Check the IDs of migrations belonging to media migration.
    $media_migrations = array_filter($migrations, function (MigrationEntityInterface $migration_config) {
      $entity_array = $migration_config->toArray();
      return in_array(MediaMigration::MIGRATION_TAG_MAIN, $entity_array['migration_tags']);
    });
    $this->assertSame([
      'upgrade_d7_file_plain_application_public',
      'upgrade_d7_file_plain_audio_public',
      'upgrade_d7_file_plain_formatter_audio',
      'upgrade_d7_file_plain_formatter_document',
      'upgrade_d7_file_plain_formatter_image',
      'upgrade_d7_file_plain_formatter_video',
      'upgrade_d7_file_plain_image_public',
      'upgrade_d7_file_plain_source_field_audio',
      'upgrade_d7_file_plain_source_field_config_audio',
      'upgrade_d7_file_plain_source_field_config_document',
      'upgrade_d7_file_plain_source_field_config_image',
      'upgrade_d7_file_plain_source_field_config_video',
      'upgrade_d7_file_plain_source_field_document',
      'upgrade_d7_file_plain_source_field_image',
      'upgrade_d7_file_plain_source_field_video',
      'upgrade_d7_file_plain_text_public',
      'upgrade_d7_file_plain_type_audio',
      'upgrade_d7_file_plain_type_document',
      'upgrade_d7_file_plain_type_image',
      'upgrade_d7_file_plain_type_video',
      'upgrade_d7_file_plain_video_public',
      'upgrade_d7_file_plain_widget_audio',
      'upgrade_d7_file_plain_widget_document',
      'upgrade_d7_file_plain_widget_image',
      'upgrade_d7_file_plain_widget_video',
    ], array_keys($media_migrations));

    $this->assertAudioMediaMigrations($media_migrations);
    $this->assertDocumentMediaMigrations($media_migrations);
    $this->assertImageMediaMigrations($media_migrations);
    $this->assertVideoMediaMigrations($media_migrations);

    // Set the migration database connection.
    // @code
    // drush php:eval \
    //   "\Drupal::state()->set('source_db', [
    //      'key' => [db-key-of-source-site],
    //      'target' => 'default',
    //    ]);"
    // @endcode
    // @code
    // drush php:eval \
    //   "\Drupal::state()->set('migrate.fallback_state_key', 'source_db');"
    // @endcode
    $this->drush('php:eval', [
      "\Drupal::state()->set('source_db', ['key' => '{$this->sourceDatabase->getKey()}', 'target' => 'default']);",
    ]);
    $this->drush('php:eval', [
      "\Drupal::state()->set('migrate.fallback_state_key', 'source_db');",
    ]);

    $this->assertArticleBodyFieldMigrationProcesses('upgrade_d7_node_complete_article', [
      [
        'plugin' => 'get',
        'source' => 'body',
      ],
      [
        'plugin' => 'media_wysiwyg_filter',
      ],
      [
        'plugin' => 'ckeditor_link_file_to_linkit',
      ],
    ]);

    // Execute the migrate import "config" drush command from the README.
    // @code
    // drush migrate:import\
    //   --execute-dependencies\
    //   --group=migrate_drupal_7\
    //   --tag=Configuration
    // @endcode
    $this->drush('migrate:import', ['--execute-dependencies'], [
      'group' => 'migrate_drupal_7',
      'tag' => 'Configuration',
    ]);

    // Execute the migrate import "config" drush command from the README.
    // @code
    // drush migrate:import\
    //   --execute-dependencies\
    //   --group=migrate_drupal_7\
    //   --tag=Content
    // @endcode
    $this->drush('migrate:import', ['--execute-dependencies'], [
      'group' => 'migrate_drupal_7',
      'tag' => 'Content',
    ]);

    $this->resetAll();

    $this->assertNonMediaToMedia1FieldValues();
    $this->assertNonMediaToMedia2FieldValues();
    $this->assertNonMediaToMedia3FieldValues();
    $this->assertNonMediaToMedia6FieldValues();
    $this->assertNonMediaToMedia7FieldValues();
    $this->assertNonMediaToMedia8FieldValues();
    $this->assertNonMediaToMedia9FieldValues();
    $this->assertNonMediaToMedia10FieldValues();
    $this->assertNonMediaToMedia11FieldValues();
    $this->assertNonMediaToMedia12FieldValues();
  }

  /**
   * Tests the Drupal 7 field storage migration.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $d7_field_migration
   *   The Drupal 7 field storage migration entity.
   */
  public function assertD7FieldMigration(MigrationEntityInterface $d7_field_migration) {
    $this->assertEquals([
      'dependencies' => [],
      'id' => 'upgrade_d7_field',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_field',
        'constants' => [
          'status' => TRUE,
          'langcode' => 'und',
        ],
        'mapMigrationProcessValueToMedia' => TRUE,
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'entity_type',
          ],
          [
            'plugin' => 'static_map',
            'map' => [
              'file' => 'media',
            ],
            'bypass' => TRUE,
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'langcode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/langcode',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'get',
            'source' => 'field_name',
          ],
        ],
        'type' => [
          [
            'plugin' => 'process_field',
            'source' => 'type',
            'method' => 'getFieldType',
            'map' => [
              'd7_text' => [
                'd7_text' => 'd7_text',
              ],
              'media_image' => [
                'media_image' => 'media_image',
              ],
              'file_entity' => [
                'file_entity' => 'file_entity',
              ],
            ],
          ],
        ],
        'cardinality' => [
          [
            'plugin' => 'get',
            'source' => 'cardinality',
          ],
        ],
        'settings' => [
          0 => [
            'plugin' => 'd7_field_settings',
          ],
          'media_image' => [
            'plugin' => 'media_image_field_settings',
          ],
          'file_entity' => [
            'plugin' => 'file_entity_field_settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_storage_config',
      ],
      'migration_dependencies' => [
        'required' => [],
        'optional' => [],
      ],
    ], $this->getImportantEntityProperties($d7_field_migration));
  }

  /**
   * Tests audio file to media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertAudioMediaMigrations(array $media_migrations) {
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_type_audio',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_type',
        'constants' => [
          'status' => TRUE,
        ],
        'mimes' => 'audio',
        'schemes' => 'public',
        'destination_media_type_id' => 'audio',
        'source_field_name' => 'field_media_audio_file',
        'media_migration_original_id' => 'd7_file_plain_type:audio',
      ],
      'process' => [
        'id' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'bundle_label',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'source' => [
          [
            'plugin' => 'get',
            'source' => 'source_plugin_id',
          ],
        ],
        'source_configuration/source_field' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_audio',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media_type',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_audio',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_audio',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_type_audio']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_audio',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_storage',
        'constants' => [
          'entity_type_id' => 'media',
          'status' => TRUE,
          'langcode' => 'und',
          'cardinality' => 1,
        ],
        'mimes' => 'audio',
        'schemes' => 'public',
        'destination_media_type_id' => 'audio',
        'source_field_name' => 'field_media_audio_file',
        'media_migration_original_id' => 'd7_file_plain_source_field:audio',
      ],
      'process' => [
        'preexisting_field_name' => [
          [
            'plugin' => 'migmag_get_entity_property',
            'source' => 'bundle',
            'entity_type_id' => 'media_type',
            'property' => 'source_configuration',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => ['source_field' => NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => ['source_field'],
          ],
        ],
        'new_field_name' => [
          [
            'plugin' => 'callback',
            'callable' => 'is_null',
            'source' => '@preexisting_field_name',
          ],
          [
            'plugin' => 'callback',
            'callable' => 'intval',
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'make_unique_entity_field',
            'source' => 'source_field_name',
            'entity_type' => 'field_storage_config',
            'field' => 'id',
            'length' => 29,
            'postfix' => '_',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@new_field_name', '@preexisting_field_name'],
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'langcode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/langcode',
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'type' => [
          [
            'plugin' => 'get',
            'source' => 'field_type',
          ],
        ],
        'cardinality' => [
          [
            'plugin' => 'get',
            'source' => 'constants/cardinality',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_storage_config',
      ],
      'migration_dependencies' => [
        'required' => [],
        'optional' => [],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_audio']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_config_audio',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_instance',
        'constants' => [
          'entity_type_id' => 'media',
          'required' => TRUE,
        ],
        'mimes' => 'audio',
        'schemes' => 'public',
        'destination_media_type_id' => 'audio',
        'source_field_name' => 'field_media_audio_file',
        'media_migration_original_id' => 'd7_file_plain_source_field_config:audio',

      ],
      'process' => [
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_audio',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'required' => [
          [
            'plugin' => 'get',
            'source' => 'constants/required',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'source_field_label',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_config',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_audio',
          'upgrade_d7_file_plain_type_audio',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_audio',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_config_audio']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_formatter_audio',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_formatter',
        'constants' => [
          'entity_type_id' => 'media',
          'view_mode' => 'default',
        ],
        'mimes' => 'audio',
        'schemes' => 'public',
        'destination_media_type_id' => 'audio',
        'source_field_name' => 'field_media_audio_file',
        'media_migration_original_id' => 'd7_file_plain_formatter:audio',

      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'view_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/view_mode',
          ],
        ],
        'final_source_field_name' => [
          [
            'plugin' => 'migmag_compare',
            'source' => ['field_name', 'source_field_name'],
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_audio',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@final_source_field_name', 'field_name'],
          ],
        ],
        'hidden' => [
          [
            'plugin' => 'get',
            'source' => 'hidden',
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_audio',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_audio',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_formatter_audio']));

    // Widget settings migration.
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_widget_audio',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_widget',
        'constants' => [
          'entity_type_id' => 'media',
          'form_mode' => 'default',
        ],
        'mimes' => 'audio',
        'schemes' => 'public',
        'destination_media_type_id' => 'audio',
        'source_field_name' => 'field_media_audio_file',
        'media_migration_original_id' => 'd7_file_plain_widget:audio',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'form_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/form_mode',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_audio',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_form_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_audio',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_audio',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_widget_audio']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_audio_public',
      'migration_tags' => [
        'Drupal 7',
        'Content',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONTENT,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain',
        'mime' => 'audio',
        'scheme' => 'public',
        'mimes' => 'audio',
        'schemes' => 'public',
        'destination_media_type_id' => 'audio',
        'source_field_name' => 'field_media_audio_file',
        'source_field_migration_id' => 'd7_file_plain_source_field_config:audio',
        'media_migration_original_id' => 'd7_file_plain:audio:public',
      ],
      'process' => [
        'uuid' => [
          [
            'plugin' => 'media_migrate_uuid',
            'source' => 'fid',
          ],
        ],
        'mid' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'uid' => [
          [
            'plugin' => 'migration_lookup',
            'migration' => 'upgrade_d7_user',
            'source' => 'uid',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => 1,
          ],
        ],
        'name' => [
          [
            'plugin' => 'get',
            'source' => 'filename',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'created' => [
          [
            'plugin' => 'get',
            'source' => 'timestamp',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'status',
          ],
        ],
        'field_media_audio_file/target_id' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'field_media_audio_file/display' => [
          [
            'plugin' => 'get',
            'source' => 'display',
          ],
        ],
        'field_media_audio_file/description' => [
          [
            'plugin' => 'get',
            'source' => 'description',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_type_audio',
          'upgrade_d7_file_plain_source_field_config_audio',
          'upgrade_d7_file',
        ],
        'optional' => [
          'upgrade_d7_user',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_audio_public']));
  }

  /**
   * Tests "document" file to media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertDocumentMediaMigrations(array $media_migrations) {
    $document_mimes = $this->dbIsPostgresql($this->sourceDatabase)
      ? 'application::text'
      : 'text::application';

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_type_document',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_type',
        'constants' => [
          'status' => TRUE,
        ],
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'media_migration_original_id' => 'd7_file_plain_type:document',
      ],
      'process' => [
        'id' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'bundle_label',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'source' => [
          [
            'plugin' => 'get',
            'source' => 'source_plugin_id',
          ],
        ],
        'source_configuration/source_field' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_document',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media_type',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_document',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_document',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_type_document']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_document',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_storage',
        'constants' => [
          'entity_type_id' => 'media',
          'status' => TRUE,
          'langcode' => 'und',
          'cardinality' => 1,
        ],
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'media_migration_original_id' => 'd7_file_plain_source_field:document',
      ],
      'process' => [
        'preexisting_field_name' => [
          [
            'plugin' => 'migmag_get_entity_property',
            'source' => 'bundle',
            'entity_type_id' => 'media_type',
            'property' => 'source_configuration',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => ['source_field' => NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => ['source_field'],
          ],
        ],
        'new_field_name' => [
          [
            'plugin' => 'callback',
            'callable' => 'is_null',
            'source' => '@preexisting_field_name',
          ],
          [
            'plugin' => 'callback',
            'callable' => 'intval',
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'make_unique_entity_field',
            'source' => 'source_field_name',
            'entity_type' => 'field_storage_config',
            'field' => 'id',
            'length' => 29,
            'postfix' => '_',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@new_field_name', '@preexisting_field_name'],
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'langcode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/langcode',
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'type' => [
          [
            'plugin' => 'get',
            'source' => 'field_type',
          ],
        ],
        'cardinality' => [
          [
            'plugin' => 'get',
            'source' => 'constants/cardinality',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_storage_config',
      ],
      'migration_dependencies' => [
        'required' => [],
        'optional' => [],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_document']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_config_document',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_instance',
        'constants' => [
          'entity_type_id' => 'media',
          'required' => TRUE,
        ],
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'media_migration_original_id' => 'd7_file_plain_source_field_config:document',
      ],
      'process' => [
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_document',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'required' => [
          [
            'plugin' => 'get',
            'source' => 'constants/required',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'source_field_label',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_config',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_document',
          'upgrade_d7_file_plain_type_document',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_document',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_config_document']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_formatter_document',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_formatter',
        'constants' => [
          'entity_type_id' => 'media',
          'view_mode' => 'default',
        ],
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'media_migration_original_id' => 'd7_file_plain_formatter:document',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'view_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/view_mode',
          ],
        ],
        'final_source_field_name' => [
          [
            'plugin' => 'migmag_compare',
            'source' => ['field_name', 'source_field_name'],
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_document',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@final_source_field_name', 'field_name'],
          ],
        ],
        'hidden' => [
          [
            'plugin' => 'get',
            'source' => 'hidden',
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_document',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_document',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_formatter_document']));

    // Widget settings migration.
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_widget_document',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_widget',
        'constants' => [
          'entity_type_id' => 'media',
          'form_mode' => 'default',
        ],
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'media_migration_original_id' => 'd7_file_plain_widget:document',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'form_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/form_mode',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_document',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_form_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_document',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_document',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_widget_document']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_application_public',
      'migration_tags' => [
        'Drupal 7',
        'Content',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONTENT,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain',
        'mime' => 'application',
        'scheme' => 'public',
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'source_field_migration_id' => 'd7_file_plain_source_field_config:document',
        'media_migration_original_id' => 'd7_file_plain:application:public',
      ],
      'process' => [
        'uuid' => [
          [
            'plugin' => 'media_migrate_uuid',
            'source' => 'fid',
          ],
        ],
        'mid' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'uid' => [
          [
            'plugin' => 'migration_lookup',
            'migration' => 'upgrade_d7_user',
            'source' => 'uid',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => 1,
          ],
        ],
        'name' => [
          [
            'plugin' => 'get',
            'source' => 'filename',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'created' => [
          [
            'plugin' => 'get',
            'source' => 'timestamp',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'status',
          ],
        ],
        'field_media_document/target_id' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'field_media_document/display' => [
          [
            'plugin' => 'get',
            'source' => 'display',
          ],
        ],
        'field_media_document/description' => [
          [
            'plugin' => 'get',
            'source' => 'description',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_type_document',
          'upgrade_d7_file_plain_source_field_config_document',
          'upgrade_d7_file',
        ],
        'optional' => [
          'upgrade_d7_user',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_application_public']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_text_public',
      'migration_tags' => [
        'Drupal 7',
        'Content',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONTENT,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain',
        'mime' => 'text',
        'scheme' => 'public',
        'mimes' => $document_mimes,
        'schemes' => 'public',
        'destination_media_type_id' => 'document',
        'source_field_name' => 'field_media_document',
        'source_field_migration_id' => 'd7_file_plain_source_field_config:document',
        'media_migration_original_id' => 'd7_file_plain:text:public',
      ],
      'process' => [
        'uuid' => [
          [
            'plugin' => 'media_migrate_uuid',
            'source' => 'fid',
          ],
        ],
        'mid' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'uid' => [
          [
            'plugin' => 'migration_lookup',
            'migration' => 'upgrade_d7_user',
            'source' => 'uid',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => 1,
          ],
        ],
        'name' => [
          [
            'plugin' => 'get',
            'source' => 'filename',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'created' => [
          [
            'plugin' => 'get',
            'source' => 'timestamp',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'status',
          ],
        ],
        'field_media_document/target_id' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'field_media_document/display' => [
          [
            'plugin' => 'get',
            'source' => 'display',
          ],
        ],
        'field_media_document/description' => [
          [
            'plugin' => 'get',
            'source' => 'description',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_type_document',
          'upgrade_d7_file_plain_source_field_config_document',
          'upgrade_d7_file',
        ],
        'optional' => [
          'upgrade_d7_user',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_text_public']));
  }

  /**
   * Tests image file to media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertImageMediaMigrations(array $media_migrations) {
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_type_image',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_type',
        'constants' => [
          'status' => TRUE,
        ],
        'mimes' => 'image',
        'schemes' => 'public',
        'destination_media_type_id' => 'image',
        'source_field_name' => 'field_media_image',
        'media_migration_original_id' => 'd7_file_plain_type:image',
      ],
      'process' => [
        'id' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'bundle_label',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'source' => [
          [
            'plugin' => 'get',
            'source' => 'source_plugin_id',
          ],
        ],
        'source_configuration/source_field' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_image',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media_type',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_image',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_image',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_type_image']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_image',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_storage',
        'constants' => [
          'entity_type_id' => 'media',
          'status' => TRUE,
          'langcode' => 'und',
          'cardinality' => 1,
        ],
        'mimes' => 'image',
        'schemes' => 'public',
        'destination_media_type_id' => 'image',
        'source_field_name' => 'field_media_image',
        'media_migration_original_id' => 'd7_file_plain_source_field:image',
      ],
      'process' => [
        'preexisting_field_name' => [
          [
            'plugin' => 'migmag_get_entity_property',
            'source' => 'bundle',
            'entity_type_id' => 'media_type',
            'property' => 'source_configuration',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => ['source_field' => NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => ['source_field'],
          ],
        ],
        'new_field_name' => [
          [
            'plugin' => 'callback',
            'callable' => 'is_null',
            'source' => '@preexisting_field_name',
          ],
          [
            'plugin' => 'callback',
            'callable' => 'intval',
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'make_unique_entity_field',
            'source' => 'source_field_name',
            'entity_type' => 'field_storage_config',
            'field' => 'id',
            'length' => 29,
            'postfix' => '_',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@new_field_name', '@preexisting_field_name'],
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'langcode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/langcode',
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'type' => [
          [
            'plugin' => 'get',
            'source' => 'field_type',
          ],
        ],
        'cardinality' => [
          [
            'plugin' => 'get',
            'source' => 'constants/cardinality',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_storage_config',
      ],
      'migration_dependencies' => [
        'required' => [],
        'optional' => [],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_image']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_config_image',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_instance',
        'constants' => [
          'entity_type_id' => 'media',
          'required' => TRUE,
        ],
        'mimes' => 'image',
        'schemes' => 'public',
        'destination_media_type_id' => 'image',
        'source_field_name' => 'field_media_image',
        'media_migration_original_id' => 'd7_file_plain_source_field_config:image',
      ],
      'process' => [
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_image',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'required' => [
          [
            'plugin' => 'get',
            'source' => 'constants/required',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'source_field_label',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_config',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_image',
          'upgrade_d7_file_plain_type_image',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_image',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_config_image']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_formatter_image',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_formatter',
        'constants' => [
          'entity_type_id' => 'media',
          'view_mode' => 'default',
        ],
        'mimes' => 'image',
        'schemes' => 'public',
        'destination_media_type_id' => 'image',
        'source_field_name' => 'field_media_image',
        'media_migration_original_id' => 'd7_file_plain_formatter:image',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'view_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/view_mode',
          ],
        ],
        'final_source_field_name' => [
          [
            'plugin' => 'migmag_compare',
            'source' => ['field_name', 'source_field_name'],
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_image',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@final_source_field_name', 'field_name'],
          ],
        ],
        'hidden' => [
          [
            'plugin' => 'get',
            'source' => 'hidden',
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_image',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_image',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_formatter_image']));

    // Widget settings migration.
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_widget_image',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_widget',
        'constants' => [
          'entity_type_id' => 'media',
          'form_mode' => 'default',
        ],
        'mimes' => 'image',
        'schemes' => 'public',
        'destination_media_type_id' => 'image',
        'source_field_name' => 'field_media_image',
        'media_migration_original_id' => 'd7_file_plain_widget:image',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'form_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/form_mode',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_image',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_form_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_image',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_image',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_widget_image']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_image_public',
      'migration_tags' => [
        'Drupal 7',
        'Content',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONTENT,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain',
        'mime' => 'image',
        'scheme' => 'public',
        'mimes' => 'image',
        'schemes' => 'public',
        'destination_media_type_id' => 'image',
        'source_field_name' => 'field_media_image',
        'source_field_migration_id' => 'd7_file_plain_source_field_config:image',
        'media_migration_original_id' => 'd7_file_plain:image:public',
      ],
      'process' => [
        'uuid' => [
          [
            'plugin' => 'media_migrate_uuid',
            'source' => 'fid',
          ],
        ],
        'mid' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'uid' => [
          [
            'plugin' => 'migration_lookup',
            'migration' => 'upgrade_d7_user',
            'source' => 'uid',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => 1,
          ],
        ],
        'name' => [
          [
            'plugin' => 'get',
            'source' => 'filename',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'created' => [
          [
            'plugin' => 'get',
            'source' => 'timestamp',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'status',
          ],
        ],
        'field_media_image/target_id' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'field_media_image/alt' => [
          [
            'plugin' => 'null_coalesce',
            'source' => [
              'alt',
              'description',
            ],
          ],
        ],
        'field_media_image/title' => [
          [
            'plugin' => 'get',
            'source' => 'title',
          ],
        ],
        'field_media_image/width' => [
          [
            'plugin' => 'get',
            'source' => 'width',
          ],
        ],
        'field_media_image/height' => [
          [
            'plugin' => 'get',
            'source' => 'height',
          ],
        ],
        'thumbnail/target_id' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'thumbnail/alt' => [
          [
            'plugin' => 'null_coalesce',
            'source' => [
              'alt',
              'description',
            ],
          ],
        ],
        'thumbnail/title' => [
          [
            'plugin' => 'get',
            'source' => 'title',
          ],
        ],
        'thumbnail/width' => [
          [
            'plugin' => 'get',
            'source' => 'width',
          ],
        ],
        'thumbnail/height' => [
          [
            'plugin' => 'get',
            'source' => 'height',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_type_image',
          'upgrade_d7_file_plain_source_field_config_image',
          'upgrade_d7_file',
        ],
        'optional' => [
          'upgrade_d7_user',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_image_public']));
  }

  /**
   * Tests video file to media migrations (of locally stored videos).
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertVideoMediaMigrations(array $media_migrations) {
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_type_video',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_type',
        'constants' => [
          'status' => TRUE,
        ],
        'mimes' => 'video',
        'schemes' => 'public',
        'destination_media_type_id' => 'video',
        'source_field_name' => 'field_media_video_file',
        'media_migration_original_id' => 'd7_file_plain_type:video',
      ],
      'process' => [
        'id' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'bundle_label',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'source' => [
          [
            'plugin' => 'get',
            'source' => 'source_plugin_id',
          ],
        ],
        'source_configuration/source_field' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_video',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media_type',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_video',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_video',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_type_video']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_video',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_storage',
        'constants' => [
          'entity_type_id' => 'media',
          'status' => TRUE,
          'langcode' => 'und',
          'cardinality' => 1,
        ],
        'mimes' => 'video',
        'schemes' => 'public',
        'destination_media_type_id' => 'video',
        'source_field_name' => 'field_media_video_file',
        'media_migration_original_id' => 'd7_file_plain_source_field:video',
      ],
      'process' => [
        'preexisting_field_name' => [
          [
            'plugin' => 'migmag_get_entity_property',
            'source' => 'bundle',
            'entity_type_id' => 'media_type',
            'property' => 'source_configuration',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => ['source_field' => NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => ['source_field'],
          ],
        ],
        'new_field_name' => [
          [
            'plugin' => 'callback',
            'callable' => 'is_null',
            'source' => '@preexisting_field_name',
          ],
          [
            'plugin' => 'callback',
            'callable' => 'intval',
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'make_unique_entity_field',
            'source' => 'source_field_name',
            'entity_type' => 'field_storage_config',
            'field' => 'id',
            'length' => 29,
            'postfix' => '_',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@new_field_name', '@preexisting_field_name'],
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'constants/status',
          ],
        ],
        'langcode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/langcode',
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'type' => [
          [
            'plugin' => 'get',
            'source' => 'field_type',
          ],
        ],
        'cardinality' => [
          [
            'plugin' => 'get',
            'source' => 'constants/cardinality',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_storage_config',
      ],
      'migration_dependencies' => [
        'required' => [],
        'optional' => [],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_video']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_source_field_config_video',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_source_field_instance',
        'constants' => [
          'entity_type_id' => 'media',
          'required' => TRUE,
        ],
        'mimes' => 'video',
        'schemes' => 'public',
        'destination_media_type_id' => 'video',
        'source_field_name' => 'field_media_video_file',
        'media_migration_original_id' => 'd7_file_plain_source_field_config:video',
      ],
      'process' => [
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_video',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'required' => [
          [
            'plugin' => 'get',
            'source' => 'constants/required',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'label' => [
          [
            'plugin' => 'get',
            'source' => 'source_field_label',
          ],
        ],
        'settings' => [
          [
            'plugin' => 'get',
            'source' => 'settings',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:field_config',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_video',
          'upgrade_d7_file_plain_type_video',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_video',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_source_field_config_video']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_formatter_video',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_formatter',
        'constants' => [
          'entity_type_id' => 'media',
          'view_mode' => 'default',
        ],
        'mimes' => 'video',
        'schemes' => 'public',
        'destination_media_type_id' => 'video',
        'source_field_name' => 'field_media_video_file',
        'media_migration_original_id' => 'd7_file_plain_formatter:video',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'view_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/view_mode',
          ],
        ],
        'final_source_field_name' => [
          [
            'plugin' => 'migmag_compare',
            'source' => ['field_name', 'source_field_name'],
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_video',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'null_coalesce',
            'source' => ['@final_source_field_name', 'field_name'],
          ],
        ],
        'hidden' => [
          [
            'plugin' => 'get',
            'source' => 'hidden',
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_video',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_video',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_formatter_video']));

    // Widget settings migration.
    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_widget_video',
      'migration_tags' => [
        'Drupal 7',
        'Configuration',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONFIG,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain_field_widget',
        'constants' => [
          'entity_type_id' => 'media',
          'form_mode' => 'default',
        ],
        'mimes' => 'video',
        'schemes' => 'public',
        'destination_media_type_id' => 'video',
        'source_field_name' => 'field_media_video_file',
        'media_migration_original_id' => 'd7_file_plain_widget:video',
      ],
      'process' => [
        'entity_type' => [
          [
            'plugin' => 'get',
            'source' => 'constants/entity_type_id',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'form_mode' => [
          [
            'plugin' => 'get',
            'source' => 'constants/form_mode',
          ],
        ],
        'field_name' => [
          [
            'plugin' => 'migration_lookup',
            'source' => ['mimes', 'schemes'],
            'migration' => 'upgrade_d7_file_plain_source_field_video',
            'no_stub' => TRUE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => [NULL, NULL],
          ],
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
        ],
        'options' => [
          [
            'plugin' => 'get',
            'source' => 'options',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'component_entity_form_display',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_source_field_config_video',
        ],
        'optional' => [
          'upgrade_d7_file_plain_source_field_video',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_widget_video']));

    $this->assertSame([
      'dependencies' => [],
      'id' => 'upgrade_d7_file_plain_video_public',
      'migration_tags' => [
        'Drupal 7',
        'Content',
        MediaMigration::MIGRATION_TAG_MAIN,
        MediaMigration::MIGRATION_TAG_CONTENT,
      ],
      'migration_group' => 'migrate_drupal_7',
      'source' => [
        'plugin' => 'd7_file_plain',
        'mime' => 'video',
        'scheme' => 'public',
        'mimes' => 'video',
        'schemes' => 'public',
        'destination_media_type_id' => 'video',
        'source_field_name' => 'field_media_video_file',
        'source_field_migration_id' => 'd7_file_plain_source_field_config:video',
        'media_migration_original_id' => 'd7_file_plain:video:public',
      ],
      'process' => [
        'uuid' => [
          [
            'plugin' => 'media_migrate_uuid',
            'source' => 'fid',
          ],
        ],
        'mid' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'uid' => [
          [
            'plugin' => 'migration_lookup',
            'migration' => 'upgrade_d7_user',
            'source' => 'uid',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => 1,
          ],
        ],
        'name' => [
          [
            'plugin' => 'get',
            'source' => 'filename',
          ],
        ],
        'bundle' => [
          [
            'plugin' => 'get',
            'source' => 'bundle',
          ],
        ],
        'created' => [
          [
            'plugin' => 'get',
            'source' => 'timestamp',
          ],
        ],
        'status' => [
          [
            'plugin' => 'get',
            'source' => 'status',
          ],
        ],
        'field_media_video_file/target_id' => [
          [
            'plugin' => 'get',
            'source' => 'fid',
          ],
        ],
        'field_media_video_file/display' => [
          [
            'plugin' => 'get',
            'source' => 'display',
          ],
        ],
        'field_media_video_file/description' => [
          [
            'plugin' => 'get',
            'source' => 'description',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:media',
      ],
      'migration_dependencies' => [
        'required' => [
          'upgrade_d7_file_plain_type_video',
          'upgrade_d7_file_plain_source_field_config_video',
          'upgrade_d7_file',
        ],
        'optional' => [
          'upgrade_d7_user',
        ],
      ],
    ], $this->getImportantEntityProperties($media_migrations['upgrade_d7_file_plain_video_public']));
  }

}
