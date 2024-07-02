<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\media_migration\MediaMigration;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForNonMediaSourceTrait;

/**
 * Tests Migrate Tools and Drush compatibility – verifies usage steps in README.
 *
 * @group media_migration
 */
class DrushWithCoreMigrationsFromFileTest extends DrushTestBase {

  use MediaMigrationAssertionsForNonMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures/drupal7_nomedia.php';
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
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
   * Test migrations provided by core Migrate API with Drush and Migrate Tools.
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
      'Group: Default (default) d7_file_plain:application:public',
      'Group: Default (default) d7_file_plain:audio:public',
      'Group: Default (default) d7_file_plain:image:public',
      'Group: Default (default) d7_file_plain:text:public',
      'Group: Default (default) d7_file_plain:video:public',
      'Group: Default (default) d7_file_plain_formatter:audio',
      'Group: Default (default) d7_file_plain_formatter:document',
      'Group: Default (default) d7_file_plain_formatter:image',
      'Group: Default (default) d7_file_plain_formatter:video',
      'Group: Default (default) d7_file_plain_source_field:audio',
      'Group: Default (default) d7_file_plain_source_field:document',
      'Group: Default (default) d7_file_plain_source_field:image',
      'Group: Default (default) d7_file_plain_source_field:video',
      'Group: Default (default) d7_file_plain_source_field_config:audio',
      'Group: Default (default) d7_file_plain_source_field_config:document',
      'Group: Default (default) d7_file_plain_source_field_config:image',
      'Group: Default (default) d7_file_plain_source_field_config:video',
      'Group: Default (default) d7_file_plain_type:audio',
      'Group: Default (default) d7_file_plain_type:document',
      'Group: Default (default) d7_file_plain_type:image',
      'Group: Default (default) d7_file_plain_type:video',
      'Group: Default (default) d7_file_plain_widget:audio',
      'Group: Default (default) d7_file_plain_widget:document',
      'Group: Default (default) d7_file_plain_widget:image',
      'Group: Default (default) d7_file_plain_widget:video',
    ]);

    $this->assertArticleBodyFieldMigrationProcesses('d7_node_complete:article', [
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

}
