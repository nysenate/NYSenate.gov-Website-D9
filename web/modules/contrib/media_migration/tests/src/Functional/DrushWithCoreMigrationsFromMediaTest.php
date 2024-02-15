<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\media_migration\MediaMigration;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;

/**
 * Tests Migrate Tools and Drush compatibility – verifies usage steps in README.
 *
 * @group media_migration
 */
class DrushWithCoreMigrationsFromMediaTest extends DrushTestBase {

  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Modify source site's file public path settings for being able to migrate
    // files. (This is required for the "d7_file" migration.)
    $source_dir = DRUPAL_ROOT . DIRECTORY_SEPARATOR . \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures/sites/default/files';
    $this->sourceDatabase->upsert('variable')
      ->key('name')
      ->fields([
        'name' => 'file_public_path',
        'value' => serialize($source_dir),
      ])
      ->execute();
  }

  /**
   * Test media migrations with Drush and Migrate Tools.
   */
  public function testMigrationWithDrush() {
    // Verify that the expected migrations are generated.
    // @code
    // drush migrate:status\
    //   --names-only\
    //   --group=default
    //   --tag="Media Migration"
    // @endcode
    $this->drush('migrate:status', ['--names-only'], [
      'group' => 'default',
      'tag' => MediaMigration::MIGRATION_TAG_MAIN,
    ]);

    $this->assertDrushMigrateStatusOutputHasAllLines([
      'Group: Default (default) d7_media_view_modes',
      'Group: Default (default) d7_file_entity_type:image',
      'Group: Default (default) d7_file_entity_type:audio',
      'Group: Default (default) d7_file_entity_source_field:audio',
      'Group: Default (default) d7_file_entity_type:document',
      'Group: Default (default) d7_file_entity_source_field:document',
      'Group: Default (default) d7_file_entity_source_field:image',
      'Group: Default (default) d7_file_entity_type:remote_video',
      'Group: Default (default) d7_file_entity_source_field:remote_video',
      'Group: Default (default) d7_file_entity_type:video',
      'Group: Default (default) d7_file_entity_source_field:video',
      'Group: Default (default) d7_file_entity_source_field_config:audio',
      'Group: Default (default) d7_file_entity_formatter:audio',
      'Group: Default (default) d7_file_entity_source_field_config:document',
      'Group: Default (default) d7_file_entity_formatter:document',
      'Group: Default (default) d7_file_entity_source_field_config:image',
      'Group: Default (default) d7_file_entity_formatter:image',
      'Group: Default (default) d7_file_entity_source_field_config:remote_video',
      'Group: Default (default) d7_file_entity_formatter:remote_video',
      'Group: Default (default) d7_file_entity_source_field_config:video',
      'Group: Default (default) d7_file_entity_formatter:video',
      'Group: Default (default) d7_file_entity:audio:public',
      'Group: Default (default) d7_file_entity:document:public',
      'Group: Default (default) d7_file_entity:image:public',
      'Group: Default (default) d7_file_entity:video:youtube',
      'Group: Default (default) d7_file_entity:video:vimeo',
      'Group: Default (default) d7_file_entity:video:public',
      'Group: Default (default) d7_file_entity_widget:audio',
      'Group: Default (default) d7_file_entity_widget:document',
      'Group: Default (default) d7_file_entity_widget:image',
      'Group: Default (default) d7_file_entity_widget:remote_video',
      'Group: Default (default) d7_file_entity_widget:video',
    ]);

    $this->assertArticleBodyFieldMigrationProcesses('d7_node_complete:article');

    // Execute the migrate import "media config" drush command.
    // @code
    // drush migrate:import\
    //   --execute-dependencies\
    //   --group=default\
    //   --tag="Media Configuration"
    // @endcode
    $this->drush('migrate:import', ['--execute-dependencies'], [
      'group' => 'default',
      'tag' => MediaMigration::MIGRATION_TAG_CONFIG,
    ]);

    // Execute the migration import "dependent config entity types" drush
    // command.
    // @code
    // drush migrate:import\
    //   --group=default\
    //   d7_node_type,d7_comment_type
    // @endcode
    $this->drush('migrate:import', ['d7_node_type,d7_comment_type'], [
      'group' => 'default',
    ]);

    // Execute the migration import "field storage and instance" drush command.
    // @code
    // drush migrate:import\
    //   --group=default\
    //   d7_field,d7_field_instance
    // @endcode
    $this->drush('migrate:import', [
      'd7_field,d7_field_instance',
    ], [
      'group' => 'default',
    ]);

    // Execute the migrations of media entities.
    // @code
    // drush migrate:import\
    //   --execute-dependencies\
    //   --group=default\
    //   --tag="Media Entity"
    // @endcode
    $this->drush('migrate:import', ['--execute-dependencies'], [
      'group' => 'default',
      'tag' => MediaMigration::MIGRATION_TAG_CONTENT,
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
   * Test all migrations with Drush and Migrate Tools.
   *
   * @depends testMigrationWithDrush
   */
  public function testAllMigrationWithDrush() {
    // Execute file migrations.
    // @code
    // drush migrate:import d7_file
    // @endcode
    $this->drush('migrate:import', ['d7_file']);

    // 'Change' back 'file_public_path' variable.
    $this->sourceDatabase->delete('variable')
      ->condition('name', 'file_public_path')
      ->execute();

    // Execute every Drupal 7 migrations.
    // @code
    // drush migrate:import --execute-dependencies --tag="Drupal 7"
    // @endcode
    $this->drush('migrate:import', ['--execute-dependencies'], [
      'tag' => 'Drupal 7',
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

}
