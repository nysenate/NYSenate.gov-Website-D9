<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;

/**
 * Tests media migration.
 *
 * @group media_migration
 */
class MediaMigrationNoImageAltFieldTest extends MediaMigrationTestBase {

  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'media',
    'media_migration',
    'media_migration_test_oembed',
    'menu_ui',
    'migmag_process',
    'migrate',
    'migrate_drupal',
    'migrate_plus',
    'smart_sql_idmap',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * Tests media migration from a site without field_file_image_alt_text field.
   */
  public function testMediaConfigMigrationWithNoAltField() {
    $this->setEmbedTokenDestinationFilterPlugin('media_embed');
    $image_property_fields = [
      'field_file_image_alt_text',
      'field_file_image_title_text',
    ];
    // Delete all data stored for media image alt and title properties.
    foreach ($image_property_fields as $prop_field_name) {
      $this->sourceDatabase->schema()->dropTable("field_data_$prop_field_name");
      $this->sourceDatabase->schema()->dropTable("field_revision_$prop_field_name");
      $this->sourceDatabase->delete('field_config')->condition('field_name', $prop_field_name)->execute();
      $this->sourceDatabase->delete('field_config_instance')->condition('field_name', $prop_field_name)->execute();
    }

    // Execute the media configuration migrations.
    $this->executeMediaConfigurationMigrations();

    // Check the migrated media types.
    $media_type_storage = $this->container->get('entity_type.manager')->getStorage('media_type');
    assert($media_type_storage instanceof EntityStorageInterface);
    // Image media type.
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'image',
      'label' => 'Image',
      'description' => NULL,
      'source' => 'image',
      'queue_thumbnail_downloads' => FALSE,
      'new_revision' => FALSE,
      'source_configuration' => ['source_field' => 'field_media_image'],
      'field_map' => [],
    ], $this->getImportantEntityProperties($media_type_storage->load('image')));

    // Check the migrated media field instances.
    $field_config_storage = $this->container->get('entity_type.manager')->getStorage('field_config');
    assert($field_config_storage instanceof EntityStorageInterface);
    // Image media source field instance.
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.image.field_media_image',
      'label' => 'Image',
      'description' => '',
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'bundle' => 'image',
      'required' => TRUE,
      'translatable' => TRUE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'alt_field' => TRUE,
        'title_field' => TRUE,
        'alt_field_required' => TRUE,
        'title_field_required' => FALSE,
        'file_extensions' => 'png gif jpg jpeg webp',
        'max_resolution' => '',
        'min_resolution' => '',
        'default_image' => [
          'uuid' => NULL,
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
        ],
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '',
        'handler' => 'default:file',
        'handler_settings' => [],
      ],
      'field_type' => 'image',
    ], $this->getImportantEntityProperties($field_config_storage->load('media.image.field_media_image')));
  }

}
