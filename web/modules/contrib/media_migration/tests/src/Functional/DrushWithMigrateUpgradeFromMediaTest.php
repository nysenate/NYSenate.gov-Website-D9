<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\Plugin\migrate\source\d7\ConfigSourceBase;
use Drupal\migrate_plus\Entity\MigrationInterface as MigrationEntityInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;

/**
 * Tests Migrate Upgrade compatibility and verifies usage steps of README.
 *
 * @group media_migration
 */
class DrushWithMigrateUpgradeFromMediaTest extends DrushTestBase {

  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * Base plugin definition of media source field storage migrations.
   *
   * @const array
   */
  const SOURCE_FIELD_STORAGE_MIGRATION_DEFINITION_BASE = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Configuration',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONFIG,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => [
      'plugin' => 'd7_file_entity_source_field_storage',
      'constants' => [
        'entity_type_id' => 'media',
        'status' => TRUE,
        'langcode' => 'und',
        'cardinality' => 1,
      ],
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
        ['plugin' => 'extract', 'index' => ['source_field']],
      ],
      'new_field_name' => [
        [
          'plugin' => 'callback',
          'callable' => 'is_null',
          'source' => '@preexisting_field_name',
        ],
        ['plugin' => 'callback', 'callable' => 'intval'],
        ['plugin' => 'skip_on_empty', 'method' => 'process'],
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
      'status' => [['plugin' => 'get', 'source' => 'constants/status']],
      'langcode' => [['plugin' => 'get', 'source' => 'constants/langcode']],
      'entity_type' => [
        ['plugin' => 'get', 'source' => 'constants/entity_type_id'],
      ],
      'type' => [['plugin' => 'get', 'source' => 'field_type']],
      'cardinality' => [
        ['plugin' => 'get', 'source' => 'constants/cardinality'],
      ],
      'settings' => [['plugin' => 'get', 'source' => 'settings']],
    ],
    'destination' => ['plugin' => 'entity:field_storage_config'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * Base plugin definition of media type migrations.
   *
   * @const array
   */
  const TYPE_MIGRATION_DEFINITION_BASE = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Configuration',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONFIG,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => [
      'plugin' => 'd7_file_entity_type',
      'constants' => [
        'status' => TRUE,
      ],
    ],
    'process' => [
      'id' => [['plugin' => 'get', 'source' => 'bundle']],
      'label' => [['plugin' => 'get', 'source' => 'bundle_label']],
      'status' => [['plugin' => 'get', 'source' => 'constants/status']],
      'source' => [['plugin' => 'get', 'source' => 'source_plugin_id']],
      'source_configuration/source_field' => [
        [
          'plugin' => 'migration_lookup',
          'source' => ['types', 'schemes'],
          'migration' => 'REPLACE ME!',
          'no_stub' => TRUE,
        ],
        ['plugin' => 'default_value', 'default_value' => [NULL, NULL]],
        ['plugin' => 'extract', 'index' => [1]],
      ],
    ],
    'destination' => ['plugin' => 'entity:media_type'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * Base plugin definition of media source field instance migrations.
   *
   * @const array
   */
  const SOURCE_FIELD_INSTANCE_MIGRATION_DEFINITION_BASE = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Configuration',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONFIG,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => [
      'plugin' => 'd7_file_entity_source_field_instance',
      'constants' => [
        'entity_type_id' => 'media',
        'required' => TRUE,
      ],
    ],
    'process' => [
      'field_name' => [
        [
          'plugin' => 'migration_lookup',
          'source' => ['types', 'schemes'],
          'migration' => 'REPLACE ME!',
          'no_stub' => TRUE,
        ],
        [
          'plugin' => 'default_value',
          'default_value' => [NULL, NULL],
        ],
        ['plugin' => 'extract', 'index' => [1]],
      ],
      'entity_type' => [
        ['plugin' => 'get', 'source' => 'constants/entity_type_id'],
      ],
      'required' => [['plugin' => 'get', 'source' => 'constants/required']],
      'bundle' => [['plugin' => 'get', 'source' => 'bundle']],
      'label' => [['plugin' => 'get', 'source' => 'source_field_label']],
      'settings' => [['plugin' => 'get', 'source' => 'settings']],
    ],
    'destination' => ['plugin' => 'entity:field_config'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * Base plugin definition of media entity migrations.
   *
   * @const array
   */
  const ENTITY_MIGRATION_DEFINITION_BASE = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Content',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONTENT,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => ['plugin' => 'd7_file_entity_item'],
    'process' => [
      'track_changes_uuid' => [
        [
          'plugin' => 'migration_lookup',
          'source' => 'fid',
          'migration' => [
            'upgrade_d7_file_entity_image_public',
            'upgrade_d7_file_entity_video_youtube',
            'upgrade_d7_file_entity_video_vimeo',
            'upgrade_d7_file_entity_video_public',
            'upgrade_d7_file_entity_document_public',
            'upgrade_d7_file_entity_audio_public',
          ],
          'no_stub' => TRUE,
        ],
        ['plugin' => 'skip_on_empty', 'method' => 'process'],
        [
          'plugin' => 'migmag_get_entity_property',
          'entity_type_id' => 'media',
          'property' => 'uuid',
        ],
      ],
      'oracle_uuid' => [['plugin' => 'media_migrate_uuid', 'source' => 'fid']],
      'uuid' => [
        [
          'plugin' => 'null_coalesce',
          'source' => ['@track_changes_uuid', '@oracle_uuid'],
        ],
      ],
      'uid' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => 'upgrade_d7_user',
          'source' => 'uid',
        ],
        ['plugin' => 'default_value', 'default_value' => 1],
      ],
      'bundle' => [['plugin' => 'get', 'source' => 'bundle']],
      'name' => [['plugin' => 'get', 'source' => 'filename']],
      'created' => [['plugin' => 'get', 'source' => 'timestamp']],
      'status' => [['plugin' => 'get', 'source' => 'status']],
    ],
    'destination' => ['plugin' => 'entity:media'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * Base plugin definition of media entity migrations on PostgreSql.
   *
   * @const array
   */
  const ENTITY_MIGRATION_DEFINITION_BASE_POSTGRES = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Content',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONTENT,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => ['plugin' => 'd7_file_entity_item'],
    'process' => [
      'track_changes_uuid' => [
        [
          'plugin' => 'migration_lookup',
          'source' => 'fid',
          'migration' => [
            'upgrade_d7_file_entity_image_public',
            'upgrade_d7_file_entity_video_public',
            'upgrade_d7_file_entity_video_vimeo',
            'upgrade_d7_file_entity_video_youtube',
            'upgrade_d7_file_entity_audio_public',
            'upgrade_d7_file_entity_document_public',
          ],
          'no_stub' => TRUE,
        ],
        ['plugin' => 'skip_on_empty', 'method' => 'process'],
        [
          'plugin' => 'migmag_get_entity_property',
          'entity_type_id' => 'media',
          'property' => 'uuid',
        ],
      ],
      'oracle_uuid' => [['plugin' => 'media_migrate_uuid', 'source' => 'fid']],
      'uuid' => [
        [
          'plugin' => 'null_coalesce',
          'source' => ['@track_changes_uuid', '@oracle_uuid'],
        ],
      ],
      'uid' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => 'upgrade_d7_user',
          'source' => 'uid',
        ],
        ['plugin' => 'default_value', 'default_value' => 1],
      ],
      'bundle' => [['plugin' => 'get', 'source' => 'bundle']],
      'name' => [['plugin' => 'get', 'source' => 'filename']],
      'created' => [['plugin' => 'get', 'source' => 'timestamp']],
      'status' => [['plugin' => 'get', 'source' => 'status']],
    ],
    'destination' => ['plugin' => 'entity:media'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * Base plugin definition of media field formatter migrations.
   *
   * @const array
   */
  const FIELD_FORMATTER_MIGRATION_DEFINITION_BASE = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Configuration',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONFIG,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => [
      'plugin' => 'd7_file_entity_field_formatter',
      'constants' => [
        'entity_type_id' => 'media',
        'view_mode' => 'default',
      ],
    ],
    'process' => [
      'entity_type' => [
        ['plugin' => 'get', 'source' => 'constants/entity_type_id'],
      ],
      'bundle' => [['plugin' => 'get', 'source' => 'bundle']],
      'view_mode' => [['plugin' => 'get', 'source' => 'constants/view_mode']],
      'final_source_field_name' => [
        [
          'plugin' => 'migmag_compare',
          'source' => ['field_name', 'source_field_name'],
        ],
        ['plugin' => 'skip_on_empty', 'method' => 'process'],
        [
          'plugin' => 'migration_lookup',
          'source' => ['types', 'schemes'],
          'migration' => 'REPLACE ME!',
          'no_stub' => TRUE,
        ],
        ['plugin' => 'default_value', 'default_value' => [NULL, NULL]],
        ['plugin' => 'extract', 'index' => [1]],
      ],
      'field_name' => [
        [
          'plugin' => 'null_coalesce',
          'source' => ['@final_source_field_name', 'field_name'],
        ],
      ],
      'hidden' => [['plugin' => 'get', 'source' => 'hidden']],
      'options' => [['plugin' => 'get', 'source' => 'options']],
    ],
    'destination' => ['plugin' => 'component_entity_display'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * Base plugin definition of media field widget migrations.
   *
   * @const array
   */
  const FIELD_WIDGET_MIGRATION_DEFINITION_BASE = [
    'dependencies' => [],
    'id' => 'REPLACE ME!',
    'migration_tags' => [
      'Drupal 7',
      'Configuration',
      MediaMigration::MIGRATION_TAG_MAIN,
      MediaMigration::MIGRATION_TAG_CONFIG,
    ],
    'migration_group' => 'migrate_drupal_7',
    'source' => [
      'plugin' => 'd7_file_entity_field_widget',
      'constants' => [
        'entity_type_id' => 'media',
        'form_mode' => 'default',
      ],
    ],
    'process' => [
      'entity_type' => [
        ['plugin' => 'get', 'source' => 'constants/entity_type_id'],
      ],
      'bundle' => [['plugin' => 'get', 'source' => 'bundle']],
      'form_mode' => [['plugin' => 'get', 'source' => 'constants/form_mode']],
      'field_name' => [
        [
          'plugin' => 'migration_lookup',
          'source' => ['types', 'schemes'],
          'migration' => 'REPLACE ME!',
          'no_stub' => TRUE,
        ],
        ['plugin' => 'default_value', 'default_value' => [NULL, NULL]],
        ['plugin' => 'extract', 'index' => [1]],
      ],
      'options' => [['plugin' => 'get', 'source' => 'options']],
    ],
    'destination' => ['plugin' => 'component_entity_form_display'],
    'migration_dependencies' => ['required' => [], 'optional' => []],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_upgrade',
  ];

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
      'legacy-root' => \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures',
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
      'upgrade_d7_file_entity_audio_public',
      'upgrade_d7_file_entity_document_public',
      'upgrade_d7_file_entity_formatter_audio',
      'upgrade_d7_file_entity_formatter_document',
      'upgrade_d7_file_entity_formatter_image',
      'upgrade_d7_file_entity_formatter_remote_video',
      'upgrade_d7_file_entity_formatter_video',
      'upgrade_d7_file_entity_image_public',
      'upgrade_d7_file_entity_source_field_audio',
      'upgrade_d7_file_entity_source_field_config_audio',
      'upgrade_d7_file_entity_source_field_config_document',
      'upgrade_d7_file_entity_source_field_config_image',
      'upgrade_d7_file_entity_source_field_config_remote_video',
      'upgrade_d7_file_entity_source_field_config_video',
      'upgrade_d7_file_entity_source_field_document',
      'upgrade_d7_file_entity_source_field_image',
      'upgrade_d7_file_entity_source_field_remote_video',
      'upgrade_d7_file_entity_source_field_video',
      'upgrade_d7_file_entity_type_audio',
      'upgrade_d7_file_entity_type_document',
      'upgrade_d7_file_entity_type_image',
      'upgrade_d7_file_entity_type_remote_video',
      'upgrade_d7_file_entity_type_video',
      'upgrade_d7_file_entity_video_public',
      'upgrade_d7_file_entity_video_vimeo',
      'upgrade_d7_file_entity_video_youtube',
      'upgrade_d7_file_entity_widget_audio',
      'upgrade_d7_file_entity_widget_document',
      'upgrade_d7_file_entity_widget_image',
      'upgrade_d7_file_entity_widget_remote_video',
      'upgrade_d7_file_entity_widget_video',
      'upgrade_d7_media_view_modes',
    ], array_keys($media_migrations));

    $this->assertAudioMediaMigrations($media_migrations);
    $this->assertDocumentMediaMigrations($media_migrations);
    $this->assertImageMediaMigrations($media_migrations);
    $this->assertRemoteVideoMediaMigrations($media_migrations);
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

    $this->assertArticleBodyFieldMigrationProcesses('upgrade_d7_node_complete_article');

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

    // Execute the migrate import "content" drush command from the README.
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

    $this->assertMedia1FieldValues();
    $this->assertMedia2FieldValues();
    $this->assertMedia3FieldValues();
    $this->assertMedia4FieldValues();
    $this->assertMedia5FieldValues();
    $this->assertMedia6FieldValues();
    $this->assertMedia7FieldValues();
    $this->assertMedia8FieldValues();
    $this->assertMedia9FieldValues();
    $this->assertMedia10FieldValues();
    $this->assertMedia11FieldValues();
    $this->assertMedia12FieldValues();
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
              'number_default' => [
                'number_default' => 'number_default',
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
   * Tests audio media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertAudioMediaMigrations(array $media_migrations) {
    $this->assertMediaTypeMigration(
      $media_migrations,
      'audio',
      'audio',
      'public',
      'field_media_audio_file',
      'audio',
      [
        'audio:public' => [
          'processes' => [
            'field_media_audio_file/target_id' => [
              ['plugin' => 'get', 'source' => 'fid'],
            ],
            'field_media_audio_file/display' => [
              ['plugin' => 'get', 'source' => 'display'],
            ],
            'field_media_audio_file/description' => [
              ['plugin' => 'get', 'source' => 'description'],
            ],
          ],
          'definition' => [
            'migration_dependencies' => [
              'required' => [
                2 => 'upgrade_d7_file',
              ],
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Tests document media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertDocumentMediaMigrations(array $media_migrations) {
    $this->assertMediaTypeMigration(
      $media_migrations,
      'document',
      'document',
      'public',
      'field_media_document',
      'document',
      [
        'document:public' => [
          'processes' => [
            'field_media_document/target_id' => [
              ['plugin' => 'get', 'source' => 'fid'],
            ],
            'field_media_document/display' => [
              ['plugin' => 'get', 'source' => 'display'],
            ],
            'field_media_document/description' => [
              ['plugin' => 'get', 'source' => 'description'],
            ],
          ],
          'definition' => [
            'migration_dependencies' => [
              'required' => [
                2 => 'upgrade_d7_file',
              ],
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Tests image media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertImageMediaMigrations(array $media_migrations) {
    $this->assertMediaTypeMigration(
      $media_migrations,
      'image',
      'image',
      'public',
      'field_media_image',
      'image',
      [
        'image:public' => [
          'processes' => [
            'field_media_integer' => [
              ['plugin' => 'get', 'source' => 'field_media_integer'],
            ],
            'field_media_image/target_id' => [
              ['plugin' => 'get', 'source' => 'fid'],
            ],
            'field_media_image/display' => [
              ['plugin' => 'get', 'source' => 'display'],
            ],
            'field_media_image/description' => [
              ['plugin' => 'get', 'source' => 'description'],
            ],
            'field_media_image/width' => [
              ['plugin' => 'get', 'source' => 'width'],
            ],
            'field_media_image/height' => [
              ['plugin' => 'get', 'source' => 'height'],
            ],
            'thumbnail/target_id' => [['plugin' => 'get', 'source' => 'fid']],
            'thumbnail/width' => [['plugin' => 'get', 'source' => 'width']],
            'thumbnail/height' => [['plugin' => 'get', 'source' => 'height']],
            'alt_from_media' => [
              [
                'plugin' => 'skip_on_empty',
                'source' => 'field_file_image_alt_text',
                'method' => 'process',
              ],
              [
                'plugin' => 'extract',
                'index' => ['0', 'value'],
                'default' => '',
              ],
              ['plugin' => 'default_value', 'default_value' => NULL],
            ],
            'thumbnail/alt' => [
              [
                'plugin' => 'null_coalesce',
                'source' => ['alt', '@alt_from_media'],
                'default_value' => NULL,
              ],
            ],
            'field_media_image/alt' => [
              [
                'plugin' => 'null_coalesce',
                'source' => ['alt', '@alt_from_media'],
                'default_value' => NULL,
              ],
            ],
            'title_from_media' => [
              [
                'plugin' => 'skip_on_empty',
                'source' => 'field_file_image_title_text',
                'method' => 'process',
              ],
              [
                'plugin' => 'extract',
                'index' => ['0', 'value'],
                'default' => '',
              ],
              ['plugin' => 'default_value', 'default_value' => NULL],
            ],
            'thumbnail/title' => [
              [
                'plugin' => 'null_coalesce',
                'source' => ['title', '@title_from_media'],
                'default_value' => NULL,
              ],
            ],
            'field_media_image/title' => [
              [
                'plugin' => 'null_coalesce',
                'source' => ['title', '@title_from_media'],
                'default_value' => NULL,
              ],
            ],
          ],
          'definition' => [
            'migration_dependencies' => [
              'required' => [
                2 => 'upgrade_d7_field_instance',
                3 => 'upgrade_d7_file',
              ],
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Tests remote video (Vimeo and YouTube) media migrations.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertRemoteVideoMediaMigrations(array $media_migrations) {
    // PostgreSQL returns differently sorted results.
    $remote_video_schemes = $this->dbIsPostgresql($this->sourceDatabase)
      ? 'vimeo::youtube'
      : 'youtube::vimeo';

    $this->assertMediaTypeMigration(
      $media_migrations,
      'remote_video',
      'video',
      $remote_video_schemes,
      'field_media_oembed_video',
      'remote_video',
      [
        'video:vimeo' => [
          'scheme' => 'vimeo',
          'processes' => [
            'field_media_oembed_video/value' => [
              [
                'plugin' => 'media_internet_field_value',
                'source' => 'uri',
              ],
            ],
          ],
        ],
        'video:youtube' => [
          'scheme' => 'youtube',
          'processes' => [
            'field_media_oembed_video/value' => [
              [
                'plugin' => 'media_internet_field_value',
                'source' => 'uri',
              ],
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Tests video media migrations (of locally stored videos).
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   Array of migration entities tagged with MediaMigration::MIGRATION_TAG.
   */
  public function assertVideoMediaMigrations(array $media_migrations) {
    $this->assertMediaTypeMigration(
      $media_migrations,
      'video',
      'video',
      'public',
      'field_media_video_file',
      'video',
      [
        'video:public' => [
          'processes' => [
            'field_media_video_file/target_id' => [
              ['plugin' => 'get', 'source' => 'fid'],
            ],
            'field_media_video_file/display' => [
              ['plugin' => 'get', 'source' => 'display'],
            ],
            'field_media_video_file/description' => [
              ['plugin' => 'get', 'source' => 'description'],
            ],
          ],
          'definition' => [
            'migration_dependencies' => [
              'required' => [
                2 => 'upgrade_d7_file',
              ],
            ],
          ],
        ],
      ]
    );
  }

  /**
   * Check the specified media migration set per migrate source (field) plugin.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $media_migrations
   *   List of available Migrate Plus migration configuration entities, keyed by
   *   their ID.
   * @param string $source_bundle
   *   The media entity type in the source.
   * @param string $types
   *   The expected 'types' source plugin configuration in migrations.
   * @param string $schemes
   *   The expected 'schemes' source plugin configuration in migrations.
   * @param string $source_field_name
   *   The expected 'source_field_name' source plugin configuration in
   *   migrations.
   * @param string $destination_media_type_id
   *   The media type ID on the destination.
   * @param array[] $expected_field_processes
   *   Array of the expected, bundle-specific entity field migration process
   *   pipelines, keyed by the expected media entity migration derivative ID.
   */
  private function assertMediaTypeMigration(array $media_migrations, string $source_bundle, string $types, string $schemes, string $source_field_name, string $destination_media_type_id, array $expected_field_processes = []): void {
    $scheme = explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $schemes)[0];
    $type = explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $types)[0];

    $this->assertSame(
      NestedArray::mergeDeepArray(
        [
          self::TYPE_MIGRATION_DEFINITION_BASE,
          [
            'id' => "upgrade_d7_file_entity_type_{$source_bundle}",
            'source' => [
              'schemes' => $schemes,
              'types' => $types,
              'destination_media_type_id' => $destination_media_type_id,
              'source_field_name' => $source_field_name,
              'media_migration_original_id' => "d7_file_entity_type:{$source_bundle}",
            ],
            'process' => [
              'source_configuration/source_field' => [
                [
                  'migration' => "upgrade_d7_file_entity_source_field_{$source_bundle}",
                ],
              ],
            ],
            'migration_dependencies' => [
              'required' => ["upgrade_d7_file_entity_source_field_{$source_bundle}"],
              'optional' => ["upgrade_d7_file_entity_source_field_{$source_bundle}"],
            ],
          ],
        ],
        TRUE
      ),
      $this->getImportantEntityProperties($media_migrations["upgrade_d7_file_entity_type_{$source_bundle}"])
    );

    $this->assertSame(
      NestedArray::mergeDeepArray(
        [
          self::SOURCE_FIELD_STORAGE_MIGRATION_DEFINITION_BASE,
          [
            'id' => "upgrade_d7_file_entity_source_field_{$source_bundle}",
            'source' => [
              'schemes' => $schemes,
              'types' => $types,
              'destination_media_type_id' => $destination_media_type_id,
              'source_field_name' => $source_field_name,
              'media_migration_original_id' => "d7_file_entity_source_field:{$source_bundle}",
            ],
          ],
        ],
        TRUE
      ),
      $this->getImportantEntityProperties($media_migrations["upgrade_d7_file_entity_source_field_{$source_bundle}"])
    );

    $this->assertSame(
      NestedArray::mergeDeepArray(
        [
          self::SOURCE_FIELD_INSTANCE_MIGRATION_DEFINITION_BASE,
          [
            'id' => "upgrade_d7_file_entity_source_field_config_{$source_bundle}",
            'source' => [
              'schemes' => $schemes,
              'types' => $types,
              'destination_media_type_id' => $destination_media_type_id,
              'source_field_name' => $source_field_name,
              'media_migration_original_id' => "d7_file_entity_source_field_config:{$source_bundle}",
            ],
            'process' => [
              'field_name' => [
                [
                  'migration' => "upgrade_d7_file_entity_source_field_{$source_bundle}",
                ],
              ],
            ],
            'migration_dependencies' => [
              'required' => [
                "upgrade_d7_file_entity_source_field_{$source_bundle}",
                "upgrade_d7_file_entity_type_{$source_bundle}",
              ],
              'optional' => ["upgrade_d7_file_entity_source_field_{$source_bundle}"],
            ],
          ],
        ],
        TRUE
      ),
      $this->getImportantEntityProperties($media_migrations["upgrade_d7_file_entity_source_field_config_{$source_bundle}"])
    );

    // Field formatter migration.
    $this->assertSame(
      NestedArray::mergeDeepArray(
        [
          self::FIELD_FORMATTER_MIGRATION_DEFINITION_BASE,
          [
            'id' => "upgrade_d7_file_entity_formatter_{$source_bundle}",
            'source' => [
              'schemes' => $schemes,
              'types' => $types,
              'destination_media_type_id' => $destination_media_type_id,
              'source_field_name' => $source_field_name,
              'media_migration_original_id' => "d7_file_entity_formatter:{$source_bundle}",
            ],
            'process' => [
              'final_source_field_name' => [
                2 => [
                  'migration' => "upgrade_d7_file_entity_source_field_{$source_bundle}",
                ],
              ],
            ],
            'migration_dependencies' => [
              'required' => [
                "upgrade_d7_file_entity_source_field_config_{$source_bundle}",
              ],
              'optional' => [
                "upgrade_d7_file_entity_source_field_{$source_bundle}",
              ],
            ],
          ],
        ],
        TRUE
      ),
      $this->getImportantEntityProperties($media_migrations["upgrade_d7_file_entity_formatter_{$source_bundle}"])
    );

    // Widget settings migration.
    $this->assertSame(
      NestedArray::mergeDeepArray(
        [
          self::FIELD_WIDGET_MIGRATION_DEFINITION_BASE,
          [
            'id' => "upgrade_d7_file_entity_widget_{$source_bundle}",
            'source' => [
              'schemes' => $schemes,
              'types' => $types,
              'destination_media_type_id' => $destination_media_type_id,
              'source_field_name' => $source_field_name,
              'media_migration_original_id' => "d7_file_entity_widget:{$source_bundle}",
            ],
            'process' => [
              'field_name' => [
                0 => [
                  'migration' => "upgrade_d7_file_entity_source_field_{$source_bundle}",
                ],
              ],
            ],
            'migration_dependencies' => [
              'required' => [
                "upgrade_d7_file_entity_source_field_config_{$source_bundle}",
              ],
              'optional' => [
                "upgrade_d7_file_entity_source_field_{$source_bundle}",
              ],
            ],
          ],
        ],
        TRUE
      ),
      $this->getImportantEntityProperties($media_migrations["upgrade_d7_file_entity_widget_{$source_bundle}"])
    );

    // Media entity migrations.
    foreach ($expected_field_processes as $derivative_id => $data) {
      $migrate_upgrade_id = 'upgrade_d7_file_entity_' . str_replace(PluginBase::DERIVATIVE_SEPARATOR, '_', $derivative_id);

      $this->assertTrue(in_array(
        explode(PluginBase::DERIVATIVE_SEPARATOR, $derivative_id)[0],
        explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $types),
        TRUE
      ));

      $base = $this->connectionIsPostgreSql()
        ? self::ENTITY_MIGRATION_DEFINITION_BASE_POSTGRES
        : self::ENTITY_MIGRATION_DEFINITION_BASE;
      $this->assertSame(
        NestedArray::mergeDeepArray(
          [
            $base,
            NestedArray::mergeDeepArray(
              [
                [
                  'id' => $migrate_upgrade_id,
                  'source' => [
                    'type' => $type,
                    'scheme' => $data['scheme'] ?? $scheme,
                    'types' => $types,
                    'schemes' => $schemes,
                    'destination_media_type_id' => $destination_media_type_id,
                    'source_field_name' => $source_field_name,
                    'source_field_migration_id' => "d7_file_entity_source_field_config:{$source_bundle}",
                    'media_migration_original_id' => "d7_file_entity:{$derivative_id}",
                  ],
                  'process' => $data['processes'],
                  'migration_dependencies' => [
                    'required' => [
                      "upgrade_d7_file_entity_type_{$source_bundle}",
                      "upgrade_d7_file_entity_source_field_config_{$source_bundle}",
                    ],
                    'optional' => ['upgrade_d7_user'],
                  ],
                ],
                $data['definition'] ?? [],
              ],
              TRUE
            ),
          ],
          TRUE
        ),
        $this->getImportantEntityProperties($media_migrations[$migrate_upgrade_id])
      );
    }
  }

}
