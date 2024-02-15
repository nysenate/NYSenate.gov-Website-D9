<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\media_migration\Traits\MediaMigrationSourceDatabaseTrait;
use Drupal\Tests\media_migration\Traits\MediaMigrationTestTrait;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d7_file_entity_source_field_instance migration source plugin.
 *
 * @covers \Drupal\media_migration\Plugin\migrate\source\d7\FileEntitySourceFieldInstance
 * @group media_migration
 */
class FileEntitySourceFieldInstanceTest extends MigrateSqlSourceTestBase {

  use MediaMigrationTestTrait;
  use MediaMigrationSourceDatabaseTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'media',
    'media_migration',
    'media_migration_test_dealer_plugins',
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerSource
   *
   * @requires extension pdo_sqlite
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL, $expected_cache_key = NULL) {
    $this->createStandardMediaTypes(TRUE);
    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water, $expected_cache_key);
  }

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $expected_audio_row = [
      'types' => 'audio',
      'schemes' => 'public',
      'bundle' => 'audio',
      'bundle_label' => 'Audio',
      'source_plugin_id' => 'audio_file',
      'source_field_name' => 'field_media_audio_file',
      'source_field_label' => 'Audio file',
      'file_extensions' => 'm4a',
    ];
    $expected_document_row = [
      'types' => 'document',
      'schemes' => 'public',
      'bundle' => 'document',
      'bundle_label' => 'Document',
      'source_plugin_id' => 'file',
      'source_field_name' => 'field_media_document',
      'source_field_label' => 'Document',
      'file_extensions' => 'txt::odt',
    ];
    $expected_image_row = [
      'types' => 'image',
      'schemes' => 'public',
      'bundle' => 'image',
      'bundle_label' => 'Image',
      'source_plugin_id' => 'image',
      'source_field_name' => 'field_media_image',
      'source_field_label' => 'Image',
      'file_extensions' => 'png::jpg::jpeg::webp',
    ];
    $expected_local_video_row = [
      'types' => 'video',
      'schemes' => 'public',
      'bundle' => 'video',
      'bundle_label' => 'Video',
      'source_plugin_id' => 'video_file',
      'source_field_name' => 'field_media_video_file',
      'source_field_label' => 'Video file',
      'file_extensions' => 'webm::mp4',
    ];
    $expected_youtube_row = [
      'types' => 'video',
      'schemes' => 'youtube',
      'bundle' => 'remote_video',
      'bundle_label' => 'Remote video',
      'source_plugin_id' => 'oembed:video',
      'source_field_name' => 'field_media_oembed_video',
      'source_field_label' => 'Video URL',
    ];
    $expected_vimeo_row = [
      'types' => 'video',
      'schemes' => 'vimeo',
      'bundle' => 'remote_video',
      'bundle_label' => 'Remote video',
      'source_plugin_id' => 'oembed:video',
      'source_field_name' => 'field_media_oembed_video',
      'source_field_label' => 'Video URL',
    ];
    $expected_remote_row = [
      'types' => 'video',
      'schemes' => 'youtube::vimeo',
      'bundle' => 'remote_video',
      'bundle_label' => 'Remote video',
      'source_plugin_id' => 'oembed:video',
      'source_field_name' => 'field_media_oembed_video',
      'source_field_label' => 'Video URL',
    ];

    return [
      'Audio' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'field_data_field_file_image_alt_text' => static::getFieldDataFieldFileImageAltTextTableData(FALSE),
          'field_data_field_file_image_title_text' => static::getFieldDataFieldFileImageTitleTextTableData(FALSE),
          'field_config_instance' => static::getFieldConfigInstanceTableData(FALSE, FALSE, FALSE, FALSE, FALSE),
          'field_config' => static::getFieldConfigTableData(FALSE),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [$expected_audio_row],
        'count' => 1,
        'plugin_configuration' => [
          'types' => 'audio',
        ],
      ],
      'Document' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'field_data_field_file_image_alt_text' => static::getFieldDataFieldFileImageAltTextTableData(FALSE),
          'field_data_field_file_image_title_text' => static::getFieldDataFieldFileImageTitleTextTableData(FALSE),
          'field_config_instance' => static::getFieldConfigInstanceTableData(FALSE, FALSE, FALSE, FALSE, FALSE),
          'field_config' => static::getFieldConfigTableData(FALSE),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [$expected_document_row],
        'count' => 1,
        'plugin_configuration' => [
          'types' => 'document',
        ],
      ],
      // Image: we use a customized Image FileEntityDealer plugin here to
      // prevent unnecessary DB selects.
      'Image' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'field_data_field_file_image_alt_text' => static::getFieldDataFieldFileImageAltTextTableData(FALSE),
          'field_data_field_file_image_title_text' => static::getFieldDataFieldFileImageTitleTextTableData(FALSE),
          'field_config_instance' => static::getFieldConfigInstanceTableData(FALSE, FALSE, FALSE, FALSE, FALSE),
          'field_config' => static::getFieldConfigTableData(FALSE),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [$expected_image_row],
        'count' => 1,
        'plugin_configuration' => [
          'types' => 'image',
          'schemes' => 'public',
        ],
      ],
      'Local video' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'field_data_field_file_image_alt_text' => static::getFieldDataFieldFileImageAltTextTableData(FALSE),
          'field_data_field_file_image_title_text' => static::getFieldDataFieldFileImageTitleTextTableData(FALSE),
          'field_config_instance' => static::getFieldConfigInstanceTableData(FALSE, FALSE, FALSE, FALSE, FALSE),
          'field_config' => static::getFieldConfigTableData(FALSE),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [$expected_local_video_row],
        'count' => 1,
        'plugin_configuration' => [
          'types' => 'video',
          'schemes' => 'public',
        ],
      ],
      'Youtube video' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [$expected_youtube_row],
        'count' => 1,
        'plugin_configuration' => [
          'types' => 'video',
          'schemes' => 'youtube',
        ],
      ],
      'Vimeo video' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [$expected_vimeo_row],
        'count' => 1,
        'plugin_configuration' => [
          'types' => 'video',
          'schemes' => 'vimeo',
        ],
      ],
      'All: plugin with default configuration' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'field_data_field_file_image_alt_text' => static::getFieldDataFieldFileImageAltTextTableData(FALSE),
          'field_data_field_file_image_title_text' => static::getFieldDataFieldFileImageTitleTextTableData(FALSE),
          'field_config_instance' => static::getFieldConfigInstanceTableData(FALSE, FALSE, FALSE, FALSE, FALSE),
          'field_config' => static::getFieldConfigTableData(FALSE),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [
          $expected_image_row,
          $expected_remote_row,
          $expected_document_row,
          $expected_local_video_row,
          $expected_audio_row,
        ],
        'count' => 5,
        'plugin_configuration' => [],
      ],
      'Missing custom scheme as config' => [
        'source_data' => [
          'file_managed' => static::getFileManagedTableData(),
          'file_usage' => static::getFileUsageTableData(),
          'field_data_field_file_image_alt_text' => static::getFieldDataFieldFileImageAltTextTableData(FALSE),
          'field_data_field_file_image_title_text' => static::getFieldDataFieldFileImageTitleTextTableData(FALSE),
          'field_config_instance' => static::getFieldConfigInstanceTableData(FALSE, FALSE, FALSE, FALSE, FALSE),
          'field_config' => static::getFieldConfigTableData(FALSE),
          'users' => static::getUsersTableData(),
        ],
        'expected_data' => [],
        'count' => 0,
        'plugin_configuration' => [
          'schemes' => '_missing_custom',
        ],
      ],
    ];
  }

}
