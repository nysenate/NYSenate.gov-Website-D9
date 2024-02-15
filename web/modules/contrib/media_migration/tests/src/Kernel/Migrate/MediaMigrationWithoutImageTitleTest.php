<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;

/**
 * Tests media migration.
 *
 * @group media_migration
 */
class MediaMigrationWithoutImageTitleTest extends MediaMigrationTestBase {

  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'editor',
    'embed',
    'entity_embed',
    'field',
    'file',
    'filter',
    'image',
    'link',
    'media',
    'media_migration',
    'media_migration_test_oembed',
    'menu_ui',
    'migmag_process',
    'migrate',
    'migrate_drupal',
    'migrate_plus',
    'node',
    'options',
    'smart_sql_idmap',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
  ];

  /**
   * Tests the migration of media entities without image title.
   */
  public function testMediaWithoutImageTitleMigration() {
    // Remove every image title record from the source database.
    $table_prefixes = ['field_data_', 'field_revision_'];
    foreach ($table_prefixes as $table_prefix) {
      $field_name = 'field_image';
      $table_name = "{$table_prefix}{$field_name}";
      $this->sourceDatabase->update($table_name)
        ->fields(["{$field_name}_title" => NULL])
        ->isNotNull("{$field_name}_title")
        ->execute();
    }
    // Remove media 'title' values.
    foreach ($table_prefixes as $table_prefix) {
      $field_name = 'field_file_image_title_text';
      $table_name = "{$table_prefix}{$field_name}";
      $this->sourceDatabase->update($table_name)
        ->fields(["{$field_name}_value" => NULL])
        ->isNotNull("{$field_name}_value")
        ->execute();
    }

    // Execute the media migrations.
    $this->executeMediaMigrations(TRUE);

    // Check configurations.
    $this->assertArticleImageFieldsAllowedTypes();
    $this->assertArticleMediaFieldsAllowedTypes();

    // Check the migrated media entities.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    assert($media_storage instanceof EntityStorageInterface);
    // Media 1.
    $dest_id_1 = $this->getDestinationIdFromSourceId(1);
    $this->assertEquals([
      'mid' => [['value' => $dest_id_1]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'Blue PNG']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1587725909']],
      'field_media_image' => [
        [
          'target_id' => '1',
          'alt' => 'Alternative text about blue.png',
          'title' => NULL,
          'width' => '1280',
          'height' => '720',
        ],
      ],
      'field_media_integer' => [
        [
          'value' => '1000',
        ],
      ],
    ], $this->getImportantEntityProperties($media_storage->load($dest_id_1)));
    // Media 2.
    $dest_id_2 = $this->getDestinationIdFromSourceId(2);
    $this->assertEquals([
      'mid' => [['value' => $dest_id_2]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'green.jpg']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1587730322']],
      'field_media_image' => [
        [
          'target_id' => '2',
          'alt' => 'Alternate text for green.jpg image',
          'title' => NULL,
          'width' => '720',
          'height' => '960',
        ],
      ],
      'field_media_integer' => [],
    ], $this->getImportantEntityProperties($media_storage->load($dest_id_2)));
    // Media 3.
    $dest_id_3 = $this->getDestinationIdFromSourceId(3);
    $this->assertEquals([
      'mid' => [['value' => $dest_id_3]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'red.jpeg']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1587726037']],
      'field_media_image' => [
        [
          'target_id' => '3',
          'alt' => 'Alternative text about red.jpeg',
          'title' => NULL,
          'width' => '1280',
          'height' => '720',
        ],
      ],
      'field_media_integer' => [
        [
          'value' => '333',
        ],
      ],
    ], $this->getImportantEntityProperties($media_storage->load($dest_id_3)));
    $this->assertMedia4FieldValues();
    $this->assertMedia5FieldValues();
    $this->assertMedia6FieldValues();
    $this->assertMedia7FieldValues();
    $this->assertMedia8FieldValues();
    $this->assertMedia9FieldValues();
    // Media 10.
    $dest_id_10 = $this->getDestinationIdFromSourceId(10);
    $this->assertEquals([
      'mid' => [['value' => $dest_id_10]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'yellow.webp']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594191582']],
      'field_media_image' => [
        [
          'target_id' => '10',
          'alt' => 'Alternative text about yellow.webp',
          'title' => NULL,
          'width' => '640',
          'height' => '400',
        ],
      ],
      'field_media_integer' => [],
    ], $this->getImportantEntityProperties($media_storage->load($dest_id_10)));
    $this->assertMedia11FieldValues();
    $this->assertMedia12FieldValues();

    // Embed title should still present.
    $this->assertNode1FieldValues([
      [
        'data-entity-type' => 'media',
        'alt' => 'Different alternative text about blue.png in the test article',
        'title' => 'Different title copy for blue.png in the test article',
        'data-align' => 'center',
        'data-entity-id' => '1',
        'data-embed-button' => 'media',
        'data-entity-embed-display' => 'view_mode:media.wysiwyg',
      ],
      [
        'data-entity-type' => 'media',
        'data-entity-id' => '7',
        'data-entity-embed-display' => 'view_mode:media.full',
        'data-embed-button' => 'media',
        'alt' => 'A yellow image',
        'title' => 'This is a yellow image',
      ],
    ]);
  }

}
