<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin\migrate\source\d7;

use Drupal\media_migration\FileEntityDealerManagerInterface;
use Drupal\Tests\media_migration\Kernel\Plugin\migrate\source\DummyMediaDealerPlugin;
use Drupal\Tests\migmag\Kernel\MigMagNativeMigrateSqlTestBase;
use Prophecy\Argument;

/**
 * Tests the file entiy item source plugin.
 *
 * @covers \Drupal\media_migration\Plugin\migrate\source\d7\FileEntityItem
 * @group media_migration
 */
class FileEntityItemTest extends MigMagNativeMigrateSqlTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_migration',
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $media_dealer = $this->prophesize(FileEntityDealerManagerInterface::class);
    $media_dealer->createInstanceFromTypeAndScheme(Argument::cetera())
      ->will(function () {
        $source_type = func_get_args()[0][0];
        $scheme = func_get_args()[0][1];
        return new DummyMediaDealerPlugin(
          [
            'scheme' => $scheme,
            'destination_media_source_plugin_id' => $source_type,
          ],
          $source_type,
          [
            'id' => $source_type,
            'destination_media_type_id_base' => $source_type,
            'destination_media_source_plugin_id' => $source_type,
          ]
        );
      });
    $this->container->set('plugin.manager.file_entity_dealer', $media_dealer->reveal());
    \Drupal::setContainer($this->container);

    $this->migration->getMigrationTags()->willReturn([]);
  }

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    return [
      'No filtering' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => 1,
            'uid' => 1,
            'filename' => 'Blue PNG',
            'uri' => 'public://blue.png',
            'filemime' => 'image/png',
            'filesize' => 9061,
            'status' => 1,
            'timestamp' => 1587725909,
            'type' => 'image',
            'scheme' => 'public',
            'bundle' => 'image',
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
            'type' => 'image',
            'scheme' => 'public',
            'bundle' => 'image',
          ],
          [
            'fid' => 4,
            'uid' => 1,
            'filename' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
            'uri' => 'youtube://v/Apqd4ff0NRI',
            'filemime' => 'video/youtube',
            'filesize' => 0,
            'status' => 1,
            'timestamp' => 1587726087,
            'type' => 'video',
            'scheme' => 'youtube',
            'bundle' => 'video_youtube',
          ],
          [
            'fid' => 2,
            'uid' => 1,
            'filename' => 'green.jpg',
            'uri' => 'public://field/image/green.jpg',
            'filemime' => 'image/jpeg',
            'filesize' => 11050,
            'status' => 1,
            'timestamp' => 1587730322,
            'type' => 'image',
            'scheme' => 'public',
            'bundle' => 'image',
          ],
          [
            'fid' => '5',
            'uid' => '1',
            'filename' => 'Responsive Images in Drupal 8',
            'uri' => 'vimeo://v/204517230',
            'filemime' => 'video/vimeo',
            'filesize' => '0',
            'status' => '1',
            'timestamp' => '1587730964',
            'type' => 'video',
            'scheme' => 'vimeo',
            'bundle' => 'video_vimeo',
          ],
          [
            'fid' => '6',
            'uid' => '1',
            'filename' => 'LICENSE.txt',
            'uri' => 'public://LICENSE.txt',
            'filemime' => 'text/plain',
            'filesize' => '18002',
            'status' => '1',
            'timestamp' => '1587731111',
            'type' => 'document',
            'scheme' => 'public',
            'bundle' => 'document',
          ],
          [
            'fid' => '7',
            'uid' => '2',
            'filename' => 'yellow.jpg',
            'uri' => 'public://field/image/yellow.jpg',
            'filemime' => 'image/jpeg',
            'filesize' => '5363',
            'status' => '1',
            'timestamp' => '1588600435',
            'type' => 'image',
            'scheme' => 'public',
            'bundle' => 'image',
          ],
          [
            'fid' => '8',
            'uid' => '2',
            'filename' => 'video.webm',
            'uri' => 'public://video.webm',
            'filemime' => 'video/webm',
            'filesize' => '18123',
            'status' => '1',
            'timestamp' => '1594037784',
            'type' => 'video',
            'scheme' => 'public',
            'bundle' => 'video',
          ],
          [
            'fid' => '9',
            'uid' => '2',
            'filename' => 'video.mp4',
            'uri' => 'public://video.mp4',
            'filemime' => 'video/mp4',
            'filesize' => '18011',
            'status' => '1',
            'timestamp' => '1594117700',
            'type' => 'video',
            'scheme' => 'public',
            'bundle' => 'video',
          ],
          [
            'fid' => '10',
            'uid' => '2',
            'filename' => 'yellow.webp',
            'uri' => 'public://yellow.webp',
            'filemime' => 'image/webp',
            'filesize' => '3238',
            'status' => '1',
            'timestamp' => '1594191582',
            'type' => 'image',
            'scheme' => 'public',
            'bundle' => 'image',
          ],
          [
            'fid' => '11',
            'uid' => '1',
            'filename' => 'audio.m4a',
            'uri' => 'public://audio.m4a',
            'filemime' => 'audio/mpeg',
            'filesize' => '10711',
            'status' => '1',
            'timestamp' => '1594193701',
            'type' => 'audio',
            'scheme' => 'public',
            'bundle' => 'audio',
          ],
          [
            'fid' => '12',
            'uid' => '2',
            'filename' => 'document.odt',
            'uri' => 'public://document.odt',
            'filemime' => 'application/vnd.oasis.opendocument.text',
            'filesize' => '8089',
            'status' => '1',
            'timestamp' => '1594201103',
            'type' => 'document',
            'scheme' => 'public',
            'bundle' => 'document',
          ],
        ],
        'count' => 12,
        'config' => [],
      ],
      'Filtering for type "video"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => '4',
            'uid' => '1',
            'filename' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
            'uri' => 'youtube://v/Apqd4ff0NRI',
            'filemime' => 'video/youtube',
            'filesize' => '0',
            'status' => '1',
            'timestamp' => '1587726087',
            'type' => 'video',
            'scheme' => 'youtube',
            'bundle' => 'video_youtube',
          ],
          [
            'fid' => '5',
            'uid' => '1',
            'filename' => 'Responsive Images in Drupal 8',
            'uri' => 'vimeo://v/204517230',
            'filemime' => 'video/vimeo',
            'filesize' => '0',
            'status' => '1',
            'timestamp' => '1587730964',
            'type' => 'video',
            'scheme' => 'vimeo',
            'bundle' => 'video_vimeo',
          ],
          [
            'fid' => '8',
            'uid' => '2',
            'filename' => 'video.webm',
            'uri' => 'public://video.webm',
            'filemime' => 'video/webm',
            'filesize' => '18123',
            'status' => '1',
            'timestamp' => '1594037784',
            'type' => 'video',
            'scheme' => 'public',
            'bundle' => 'video',
          ],
          [
            'fid' => '9',
            'uid' => '2',
            'filename' => 'video.mp4',
            'uri' => 'public://video.mp4',
            'filemime' => 'video/mp4',
            'filesize' => '18011',
            'status' => '1',
            'timestamp' => '1594117700',
            'type' => 'video',
            'scheme' => 'public',
            'bundle' => 'video',
          ],
        ],
        'count' => 4,
        'config' => [
          'type' => 'video',
        ],
      ],
      'Filtering for scheme "youtube"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => '4',
            'uid' => '1',
            'filename' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
            'uri' => 'youtube://v/Apqd4ff0NRI',
            'filemime' => 'video/youtube',
            'filesize' => '0',
            'status' => '1',
            'timestamp' => '1587726087',
            'type' => 'video',
            'scheme' => 'youtube',
            'bundle' => 'video_youtube',
          ],
        ],
        'count' => 1,
        'config' => [
          'scheme' => 'youtube',
        ],
      ],
      'Filtering for uri prefix "youtube"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => '4',
            'uid' => '1',
            'filename' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
            'uri' => 'youtube://v/Apqd4ff0NRI',
            'filemime' => 'video/youtube',
            'filesize' => '0',
            'status' => '1',
            'timestamp' => '1587726087',
            'type' => 'video',
            'scheme' => 'youtube',
            'bundle' => 'video_youtube',
            'uri_prefix' => 'youtube',
          ],
        ],
        'count' => 1,
        'config' => [
          'uri_prefix' => 'youtube',
        ],
      ],
      'Filtering for uri prefix "vimeo://"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => '5',
            'uid' => '1',
            'filename' => 'Responsive Images in Drupal 8',
            'uri' => 'vimeo://v/204517230',
            'filemime' => 'video/vimeo',
            'filesize' => '0',
            'status' => '1',
            'timestamp' => '1587730964',
            'type' => 'video',
            'scheme' => 'vimeo',
            'bundle' => 'video_vimeo',
            'uri_prefix' => 'vimeo://',
          ],
        ],
        'count' => 1,
        'config' => [
          'uri_prefix' => 'vimeo://',
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
    'system' => [
      [
        'name' => 'file_entity',
        'schema_version' => 7001,
        'type' => 'module',
        'status' => 1,
      ],
    ],
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
        'type' => 'image',
      ],
      [
        'fid' => 2,
        'uid' => 1,
        'filename' => 'green.jpg',
        'uri' => 'public://field/image/green.jpg',
        'filemime' => 'image/jpeg',
        'filesize' => 11050,
        'status' => 1,
        'timestamp' => 1587730322,
        'type' => 'image',
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
        'type' => 'image',
      ],
      [
        'fid' => 4,
        'uid' => 1,
        'filename' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
        'uri' => 'youtube://v/Apqd4ff0NRI',
        'filemime' => 'video/youtube',
        'filesize' => 0,
        'status' => 1,
        'timestamp' => 1587726087,
        'type' => 'video',
      ],
      [
        'fid' => 5,
        'uid' => 1,
        'filename' => 'Responsive Images in Drupal 8',
        'uri' => 'vimeo://v/204517230',
        'filemime' => 'video/vimeo',
        'filesize' => 0,
        'status' => 1,
        'timestamp' => 1587730964,
        'type' => 'video',
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
        'type' => 'document',
      ],
      [
        'fid' => 7,
        'uid' => 2,
        'filename' => 'yellow.jpg',
        'uri' => 'public://field/image/yellow.jpg',
        'filemime' => 'image/jpeg',
        'filesize' => 5363,
        'status' => 1,
        'timestamp' => 1588600435,
        'type' => 'image',
      ],
      [
        'fid' => 8,
        'uid' => 2,
        'filename' => 'video.webm',
        'uri' => 'public://video.webm',
        'filemime' => 'video/webm',
        'filesize' => 18123,
        'status' => 1,
        'timestamp' => 1594037784,
        'type' => 'video',
      ],
      [
        'fid' => 9,
        'uid' => 2,
        'filename' => 'video.mp4',
        'uri' => 'public://video.mp4',
        'filemime' => 'video/mp4',
        'filesize' => 18011,
        'status' => 1,
        'timestamp' => 1594117700,
        'type' => 'video',
      ],
      [
        'fid' => 10,
        'uid' => 2,
        'filename' => 'yellow.webp',
        'uri' => 'public://yellow.webp',
        'filemime' => 'image/webp',
        'filesize' => 3238,
        'status' => 1,
        'timestamp' => 1594191582,
        'type' => 'image',
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
        'type' => 'audio',
      ],
      [
        'fid' => 12,
        'uid' => 2,
        'filename' => 'document.odt',
        'uri' => 'public://document.odt',
        'filemime' => 'application/vnd.oasis.opendocument.text',
        'filesize' => 8089,
        'status' => 1,
        'timestamp' => 1594201103,
        'type' => 'document',
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
      [
        'uid' => 2,
        'name' => 'editor',
        'pass' => 'asdf',
        'mail' => 'editor@drupal7-media.localhost',
        'theme' => '',
        'signature' => '',
        'signature_format' => NULL,
        'created' => 1588600077,
        'access' => 1594201082,
        'login' => 1594201082,
        'status' => 1,
        'timezone' => 'Europe/Paris',
        'language' => '',
        'picture' => 0,
        'init' => 'editor@drupal7-media.localhost',
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
