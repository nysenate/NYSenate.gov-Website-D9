<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migmag\Kernel\MigMagNativeMigrateSqlTestBase;

/**
 * Tests the d7_file_plain_source_field_instance source plugin.
 *
 * @covers \Drupal\media_migration\Plugin\migrate\source\d7\FilePlainSourceFieldInstance
 *
 * @group media_migration
 */
class FilePlainSourceFieldInstanceTest extends MigMagNativeMigrateSqlTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'media',
    'system',
    'media_migration',
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $db_is_postgre = in_array(
      explode('://', getenv('SIMPLETEST_DB'))[0],
      ['pgsql', 'postgres', 'postgresql']
    );

    $image_public = [
      'mimes' => 'image',
      'schemes' => 'public',
      'file_extensions' => $db_is_postgre ? 'jpeg::jpg::png::webp' : 'png::jpeg::jpg::webp',
      'bundle' => 'image',
      'bundle_label' => 'Image',
      'source_plugin_id' => 'image',
      'source_field_name' => 'field_media_image',
      'source_field_label' => 'Image',
      'settings' => [
        'file_extensions' => 'png gif jpg jpeg webp',
        'alt_field' => 1,
        'alt_field_required' => 1,
        'title_field' => 0,
        'title_field_required' => 0,
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
        'handler' => 'default',
        'handler_settings' => [],
        'target_type' => 'file',
        'display_field' => FALSE,
        'display_default' => FALSE,
        'uri_scheme' => 'public',
      ],
    ];
    $image_private = [
      'mimes' => 'image',
      'schemes' => 'private',
      'file_extensions' => 'jpg',
      'bundle' => 'image_private',
      'bundle_label' => 'Image (private)',
      'source_plugin_id' => 'image',
      'source_field_name' => 'field_media_image_private',
      'source_field_label' => 'Image',
      'settings' => [
        'file_extensions' => 'png gif jpg jpeg',
        'alt_field' => 1,
        'alt_field_required' => 1,
        'title_field' => 0,
        'title_field_required' => 0,
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
        'handler' => 'default',
        'handler_settings' => [],
        'target_type' => 'file',
        'display_field' => FALSE,
        'display_default' => FALSE,
        'uri_scheme' => 'private',
      ],
    ];
    $document = [
      'mimes' => $db_is_postgre ? 'application::text' : 'text::application',
      'schemes' => 'public',
      'file_extensions' => $db_is_postgre ? 'odt::txt' : 'txt::odt',
      'bundle' => 'document',
      'bundle_label' => 'Document',
      'source_plugin_id' => 'file',
      'source_field_name' => 'field_media_document',
      'source_field_label' => 'Document',
      'settings' => [
        'file_extensions' => 'txt doc docx pdf odt',
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '',
        'description_field' => FALSE,
        'handler' => 'default',
        'handler_settings' => [],
        'target_type' => 'file',
        'display_field' => FALSE,
        'display_default' => FALSE,
        'uri_scheme' => 'public',
      ],
    ];
    $audio = [
      'mimes' => 'audio',
      'schemes' => 'public',
      'file_extensions' => 'm4a',
      'bundle' => 'audio',
      'bundle_label' => 'Audio file',
      'source_plugin_id' => 'audio_file',
      'source_field_name' => 'field_media_audio_file',
      'source_field_label' => 'Audio file',
      'settings' => [
        'file_extensions' => 'mp3 wav aac m4a',
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '',
        'description_field' => FALSE,
        'handler' => 'default',
        'handler_settings' => [],
        'target_type' => 'file',
        'display_field' => FALSE,
        'display_default' => FALSE,
        'uri_scheme' => 'public',
      ],
    ];
    $video = [
      'mimes' => 'video',
      'schemes' => 'public',
      'file_extensions' => $db_is_postgre ? 'mp4::webm' : 'webm::mp4',
      'bundle' => 'video',
      'bundle_label' => 'Video file',
      'source_plugin_id' => 'video_file',
      'source_field_name' => 'field_media_video_file',
      'source_field_label' => 'Video file',
      'settings' => [
        'file_extensions' => 'mp4 webm',
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '',
        'description_field' => FALSE,
        'handler' => 'default',
        'handler_settings' => [],
        'target_type' => 'file',
        'display_field' => FALSE,
        'display_default' => FALSE,
        'uri_scheme' => 'public',
      ],
    ];

    return [
      'No filtering' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => $db_is_postgre ? [
          $image_private,
          $document,
          $audio,
          $image_public,
          $video,
        ] : [
          $image_public,
          $image_private,
          $document,
          $video,
          $audio,
        ],
        'count' => NULL,
        'config' => [],
      ],
      'Filtering for mime "image"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => $db_is_postgre
        ? [$image_private, $image_public]
        : [$image_public, $image_private],
        'count' => NULL,
        'config' => [
          'mimes' => 'image',
        ],
      ],
      'Filtering for scheme "private" and mime "image"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [$image_private],
        'count' => NULL,
        'config' => [
          'schemes' => 'private',
          'mimes' => 'image',
        ],
      ],
    ];
  }

  /**
   * The test database.
   *
   * @const array[]
   */
  const SOURCE_DATABASE = [
    'file_managed' => [
      [
        'fid' => 1,
        'uid' => 1,
        'filename' => 'Blue PNG',
        'uri' => 'public://blue.png',
        'filemime' => 'image/png',
        'filesize' => 9061,
        'status' => 1,
        'timestamp' => 1587725909,
      ],
      [
        'fid' => 2,
        'uid' => 1,
        'filename' => 'green.jpg',
        'uri' => 'private://field/image/green.jpg',
        'filemime' => 'image/jpeg',
        'filesize' => 11050,
        'status' => 1,
        'timestamp' => 1587730322,
      ],
      [
        'fid' => 3,
        'uid' => 1,
        'filename' => 'red.jpeg',
        'uri' => 'public://red.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => 19098,
        'status' => 1,
        'timestamp' => 1587726037,
      ],
      [
        'fid' => 4,
        'uid' => 1,
        'filename' => 'foo.jpeg',
        'uri' => 'public://foo.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => 1,
        'status' => 0,
        'timestamp' => 1587726137,
      ],
      [
        'fid' => 5,
        'uid' => 1,
        'filename' => 'foo',
        'uri' => '',
        'filemime' => 'application/octet-stream',
        'filesize' => 0,
        'status' => 1,
        'timestamp' => 1587723337,
      ],
      [
        'fid' => 6,
        'uid' => 1,
        'filename' => 'LICENSE.txt',
        'uri' => 'public://LICENSE.txt',
        'filemime' => 'text/plain',
        'filesize' => 18002,
        'status' => 1,
        'timestamp' => 1587731111,
      ],
      [
        'fid' => 7,
        'uid' => 1,
        'filename' => 'yellow.jpg',
        'uri' => 'public://field/image/yellow.jpg',
        'filemime' => 'image/jpeg',
        'filesize' => 5363,
        'status' => 1,
        'timestamp' => 1588600435,
      ],
      [
        'fid' => 8,
        'uid' => 1,
        'filename' => 'video.webm',
        'uri' => 'public://video.webm',
        'filemime' => 'video/webm',
        'filesize' => 18123,
        'status' => 1,
        'timestamp' => 1594037784,
      ],
      [
        'fid' => 9,
        'uid' => 1,
        'filename' => 'video.mp4',
        'uri' => 'public://video.mp4',
        'filemime' => 'video/mp4',
        'filesize' => 18011,
        'status' => 1,
        'timestamp' => 1594117700,
      ],
      [
        'fid' => 10,
        'uid' => 1,
        'filename' => 'yellow.webp',
        'uri' => 'public://yellow.webp',
        'filemime' => 'image/webp',
        'filesize' => 3238,
        'status' => 1,
        'timestamp' => 1594191582,
      ],
      [
        'fid' => 11,
        'uid' => 1,
        'filename' => 'audio.m4a',
        'uri' => 'public://audio.m4a',
        'filemime' => 'audio/mpeg',
        'filesize' => 10711,
        'status' => 1,
        'timestamp' => 1594193701,
      ],
      [
        'fid' => 12,
        'uid' => 1,
        'filename' => 'document.odt',
        'uri' => 'public://document.odt',
        'filemime' => 'application/vnd.oasis.opendocument.text',
        'filesize' => 8089,
        'status' => 1,
        'timestamp' => 1594201103,
      ],
    ],
    'file_usage' => [
      [
        'fid' => 1,
        'module' => 'media',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 2,
        'module' => 'file',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 3,
        'module' => 'file',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 4,
        'module' => 'file',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 7,
        'module' => 'file',
        'type' => 'node',
        'id' => 2,
        'count' => 1,
      ],
    ],
    'users' => [
      [
        'uid' => 0,
        'name' => '',
        'pass' => '',
        'mail' => '',
        'theme' => '',
        'signature' => '',
        'signature_format' => NULL,
        'created' => 0,
        'access' => 0,
        'login' => 0,
        'status' => 0,
        'timezone' => NULL,
        'language' => '',
        'picture' => 0,
        'init' => '',
        'data' => NULL,
      ],
      [
        'uid' => 1,
        'name' => 'user',
        'pass' => 'asdf',
        'mail' => 'user@drupal7-media.localhost',
        'theme' => '',
        'signature' => '',
        'signature_format' => NULL,
        'created' => 1587723957,
        'access' => 1594201035,
        'login' => 1594201035,
        'status' => 1,
        'timezone' => 'America/New_York',
        'language' => '',
        'picture' => 0,
        'init' => 'user@drupal7-media.localhost',
        'data' => 'b:0;',
      ],
    ],
    'field_config_instance' => [
      [
        'id' => 1,
        'field_id' => 1,
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
        'deleted' => 0,
      ],
    ],
    'field_config' => [
      [
        'id' => 1,
        'field_name' => 'body',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 0,
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:4:"node";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:6:"format";a:2:{s:5:"table";s:13:"filter_format";s:7:"columns";a:1:{s:6:"format";s:6:"format";}}}s:7:"indexes";a:1:{s:6:"format";a:1:{i:0;s:6:"format";}}}',
        'cardinality' => 1,
        'translatable' => 0,
        'deleted' => 0,
      ],
    ],
  ];

}
