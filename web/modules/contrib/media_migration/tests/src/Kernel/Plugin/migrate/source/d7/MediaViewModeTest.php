<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d7_media_view_mode migration source plugin.
 *
 * @covers \Drupal\media_migration\Plugin\migrate\source\d7\MediaViewMode
 * @group media_migration
 */
class MediaViewModeTest extends MigrateSqlSourceTestBase {

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
  public function providerSource() {
    $default_view_mode_rows = [
      ['mode' => 'full'],
      ['mode' => 'preview'],
      ['mode' => 'rss'],
      ['mode' => 'teaser'],
    ];

    return [
      'Defaults' => [
        'source_data' => [
          'system' => [
            ['name' => 'file_entity', 'type' => 'module', 'status' => 1],
          ],
        ],
        'expected_data' => $default_view_mode_rows,
      ],
      'Defaults + search' => [
        'source_data' => [
          'system' => [
            ['name' => 'file_entity', 'type' => 'module', 'status' => 1],
            ['name' => 'search', 'type' => 'module', 'status' => 1],
          ],
        ],
        'expected_data' => array_merge(
          $default_view_mode_rows,
          [
            ['mode' => 'search_index'],
            ['mode' => 'search_result'],
          ]
        ),
      ],
      'Defaults + media wysiwyg' => [
        'source_data' => [
          'system' => [
            ['name' => 'file_entity', 'type' => 'module', 'status' => 1],
            ['name' => 'media_wysiwyg', 'type' => 'module', 'status' => 1],
          ],
        ],
        'expected_data' => array_merge(
          $default_view_mode_rows,
          [
            ['mode' => 'wysiwyg'],
          ]
        ),
      ],
      'Defaults + search + media wysiwyg' => [
        'source_data' => [
          'system' => [
            ['name' => 'file_entity', 'type' => 'module', 'status' => 1],
            ['name' => 'search', 'type' => 'module', 'status' => 1],
            ['name' => 'media_wysiwyg', 'type' => 'module', 'status' => 1],
          ],
        ],
        'expected_data' => array_merge(
          $default_view_mode_rows,
          [
            ['mode' => 'search_index'],
            ['mode' => 'search_result'],
            ['mode' => 'wysiwyg'],
          ]
        ),
      ],
    ];
  }

}
