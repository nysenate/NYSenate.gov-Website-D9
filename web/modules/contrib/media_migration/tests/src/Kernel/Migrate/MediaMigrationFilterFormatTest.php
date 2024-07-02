<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Component\Utility\NestedArray;
use Drupal\media_migration\MediaMigration;
use Drupal\migrate\MigrateExecutable;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsBaseTrait;
use Drupal\Tests\migmag\Traits\MigMagMigrationTestDatabaseTrait;

/**
 * Tests the Media Migration altered filter format migration.
 *
 * @group media_migration
 */
class MediaMigrationFilterFormatTest extends MediaMigrationTestBase {

  use MediaMigrationAssertionsBaseTrait;
  use MigMagMigrationTestDatabaseTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'embed',
    'entity_embed',
    'file',
    'filter',
    'linkit',
    'image',
    'media',
    'media_migration',
    'migrate',
    'migrate_drupal',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return '';
  }

  /**
   * Tests the alterations made on the filter format migration.
   *
   * @dataProvider providerTestFilterFormatMigration
   */
  public function testFilterFormatMigration(string $destination_media_embed_filter, array $source_data, array $expected_filter) {
    $this->importSourceDatabase($source_data);
    $this->setEmbedTokenDestinationFilterPlugin($destination_media_embed_filter);
    if ($destination_media_embed_filter === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED) {
      $migration = $this->getMigration('d7_embed_button_media');
      $executable = new MigrateExecutable($migration, $this);
      $executable->import();
    }

    $migration = $this->getMigration('d7_filter_format');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $filtered_html_filter_format = $this->container->get('entity_type.manager')->getStorage('filter_format')->load('filtered_html');

    $this->assertEquals($expected_filter,
      $this->getImportantEntityProperties($filtered_html_filter_format));
  }

  /**
   * Test cases for ::testFilterFormatMigration.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestFilterFormatMigration() {
    return [
      'Entity embed, no image and linkit tags' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED,
        'Source' => self::TEST_DATABASE,
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-entity data-*>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'entity_embed' => [
              'id' => 'entity_embed',
              'provider' => 'entity_embed',
              'status' => TRUE,
              'weight' => -3,
              'settings' => [],
            ],
          ],
        ],
      ],
      'Entity embed, image tags in text fields, but no linkit tags' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED,
        'Source' => self::TEST_DATABASE + self::FIELD_TABLES_WITH_IMAGE_TAG,
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-entity data-*>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'entity_embed' => [
              'id' => 'entity_embed',
              'provider' => 'entity_embed',
              'status' => TRUE,
              'weight' => 3,
              'settings' => [],
            ],
            'filter_align' => [
              'id' => 'filter_align',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 1,
              'settings' => [],
            ],
            'filter_caption' => [
              'id' => 'filter_caption',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 2,
              'settings' => [],
            ],
          ],
        ],
      ],
      'Entity embed, linkit tags in text fields, but no img tags' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED,
        'Source' => self::TEST_DATABASE + self::FIELD_TABLES_WITH_LINKIT_TAG,
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-entity data-*>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'entity_embed' => [
              'id' => 'entity_embed',
              'provider' => 'entity_embed',
              'status' => TRUE,
              'weight' => -3,
              'settings' => [],
            ],
            'linkit' => [
              'id' => 'linkit',
              'provider' => 'linkit',
              'status' => TRUE,
              'weight' => 0,
              'settings' => ['title' => TRUE],
            ],
          ],
        ],
      ],
      'Entity embed, image and linkit tags in text fields' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED,
        'Source' => self::TEST_DATABASE + NestedArray::mergeDeep(
          self::FIELD_TABLES_WITH_IMAGE_TAG,
          self::FIELD_TABLES_WITH_LINKIT_TAG
        ),
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-entity data-*>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'entity_embed' => [
              'id' => 'entity_embed',
              'provider' => 'entity_embed',
              'status' => TRUE,
              'weight' => 3,
              'settings' => [],
            ],
            'filter_align' => [
              'id' => 'filter_align',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 1,
              'settings' => [],
            ],
            'filter_caption' => [
              'id' => 'filter_caption',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 2,
              'settings' => [],
            ],
            'linkit' => [
              'id' => 'linkit',
              'provider' => 'linkit',
              'status' => TRUE,
              'weight' => 0,
              'settings' => ['title' => TRUE],
            ],
          ],
        ],
      ],
      'Media embed, no image and linkit tags' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED,
        'Source' => self::TEST_DATABASE,
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-media data-* alt title>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'media_embed' => [
              'id' => 'media_embed',
              'provider' => 'media',
              'status' => TRUE,
              'weight' => -3,
              'settings' => [
                'default_view_mode' => 'default',
                'allowed_view_modes' => [],
                'allowed_media_types' => [],
              ],
            ],
          ],
        ],
      ],
      'Media embed, image tags in text fields, but no linkit tags' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED,
        'Source' => self::TEST_DATABASE + self::FIELD_TABLES_WITH_IMAGE_TAG,
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-media data-* alt title>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'media_embed' => [
              'id' => 'media_embed',
              'provider' => 'media',
              'status' => TRUE,
              'weight' => 3,
              'settings' => [
                'default_view_mode' => 'default',
                'allowed_view_modes' => [],
                'allowed_media_types' => [],
              ],
            ],
            'filter_align' => [
              'id' => 'filter_align',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 1,
              'settings' => [],
            ],
            'filter_caption' => [
              'id' => 'filter_caption',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 2,
              'settings' => [],
            ],
          ],
        ],
      ],
      'Media embed, linkit tags in text fields, but no img tags' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED,
        'Source' => self::TEST_DATABASE + self::FIELD_TABLES_WITH_LINKIT_TAG,
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-media data-* alt title>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'media_embed' => [
              'id' => 'media_embed',
              'provider' => 'media',
              'status' => TRUE,
              'weight' => -3,
              'settings' => [
                'default_view_mode' => 'default',
                'allowed_view_modes' => [],
                'allowed_media_types' => [],
              ],
            ],
            'linkit' => [
              'id' => 'linkit',
              'provider' => 'linkit',
              'status' => TRUE,
              'weight' => 0,
              'settings' => ['title' => TRUE],
            ],
          ],
        ],
      ],
      'Media embed, image and linkit tags in text fields' => [
        'Destination filter' => MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED,
        'Source' => self::TEST_DATABASE + NestedArray::mergeDeep(
          self::FIELD_TABLES_WITH_IMAGE_TAG,
          self::FIELD_TABLES_WITH_LINKIT_TAG
        ),
        'Expected filter format' => [
          'status' => TRUE,
          'name' => 'Filtered HTML',
          'format' => 'filtered_html',
          'weight' => 0,
          'filters' => [
            'filter_autop' => [
              'id' => 'filter_autop',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -1,
              'settings' => [],
            ],
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -4,
              'settings' => [
                'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-media data-* alt title>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
            'filter_htmlcorrector' => [
              'id' => 'filter_htmlcorrector',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 0,
              'settings' => [],
            ],
            'filter_url' => [
              'id' => 'filter_url',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -2,
              'settings' => ['filter_url_length' => 72],
            ],
            'media_embed' => [
              'id' => 'media_embed',
              'provider' => 'media',
              'status' => TRUE,
              'weight' => 3,
              'settings' => [
                'default_view_mode' => 'default',
                'allowed_view_modes' => [],
                'allowed_media_types' => [],
              ],
            ],
            'filter_align' => [
              'id' => 'filter_align',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 1,
              'settings' => [],
            ],
            'filter_caption' => [
              'id' => 'filter_caption',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => 2,
              'settings' => [],
            ],
            'linkit' => [
              'id' => 'linkit',
              'provider' => 'linkit',
              'status' => TRUE,
              'weight' => 0,
              'settings' => ['title' => TRUE],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Base test database.
   *
   * @const array[]
   */
  const TEST_DATABASE = [
    'system' => [
      [
        'name' => 'filter',
        'schema_version' => 7001,
        'type' => 'module',
        'status' => 1,
      ],
      [
        'name' => 'text',
        'schema_version' => 7001,
        'type' => 'module',
        'status' => 1,
      ],
      [
        'name' => 'linkit',
        'schema_version' => 7001,
        'type' => 'module',
        'status' => 1,
      ],
    ],
    'filter_format' => [
      [
        'format' => 'filtered_html',
        'name' => 'Filtered HTML',
        'cache' => 1,
        'status' => 1,
        'weight' => 0,
      ],
    ],
    'filter' => [
      [
        'format' => 'filtered_html',
        'module' => 'filter',
        'name' => 'filter_autop',
        'weight' => '-1',
        'status' => '1',
        'settings' => 'a:0:{}',
      ],
      [
        'format' => 'filtered_html',
        'module' => 'filter',
        'name' => 'filter_html',
        'weight' => '-4',
        'status' => '1',
        'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
      ],
      [
        'format' => 'filtered_html',
        'module' => 'filter',
        'name' => 'filter_htmlcorrector',
        'weight' => '0',
        'status' => '1',
        'settings' => 'a:0:{}',
      ],
      [
        'format' => 'filtered_html',
        'module' => 'filter',
        'name' => 'filter_html_escape',
        'weight' => '-50',
        'status' => '0',
        'settings' => 'a:0:{}',
      ],
      [
        'format' => 'filtered_html',
        'module' => 'filter',
        'name' => 'filter_url',
        'weight' => '-2',
        'status' => '1',
        'settings' => 'a:1:{s:17:"filter_url_length";s:2:"72";}',
      ],
      [
        'format' => 'filtered_html',
        'module' => 'media_wysiwyg',
        'name' => 'media_filter',
        'weight' => '-3',
        'status' => '1',
        'settings' => 'a:0:{}',
      ],
      [
        'format' => 'filtered_html',
        'module' => 'media_wysiwyg',
        'name' => 'media_filter_paragraph_fix',
        'weight' => '-46',
        'status' => '0',
        'settings' => 'a:1:{s:7:"replace";i:0;}',
      ],
    ],
    'file_managed' => [
      ['fid' => 1],
    ],
  ];

  /**
   * Test database tables with a text field containing an image tag.
   *
   * @const array[]
   */
  const FIELD_TABLES_WITH_IMAGE_TAG = [
    'field_config' => [
      [
        'id' => 1,
        'field_name' => 'body_img',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => 1,
        'storage_active' => 1,
        'translatable' => 0,
        'deleted' => 0,
      ],
    ],
    'field_config_instance' => [
      [
        'id' => 1,
        'field_id' => 1,
        'field_name' => 'body_img',
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => 0,
      ],
    ],
    'field_revision_body_img' => [
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'und',
        'delta' => 0,
        'body_img_value' => '<p>Foo bar.</p><p><img src="/sites/default/files/field/image/yellow.jpg" alt="A yellow image" title="This is a yellow image" /></p>',
        'body_img_summary' => '',
        'body_img_format' => 'filtered_html',
      ],
    ],
  ];

  /**
   * Test database tables with a text field containing a linkit file link.
   *
   * @const array[]
   */
  const FIELD_TABLES_WITH_LINKIT_TAG = [
    'field_config' => [
      [
        'id' => 2,
        'field_name' => 'body_linkit',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => 1,
        'storage_active' => 1,
        'translatable' => 0,
        'deleted' => 0,
      ],
    ],
    'field_config_instance' => [
      [
        'id' => 2,
        'field_id' => 2,
        'field_name' => 'body_linkit',
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => 0,
      ],
    ],
    'field_revision_body_linkit' => [
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'und',
        'delta' => 0,
        'body_linkit_value' => '<p>Foo <a href="/file/1">bar</a>.</p>',
        'body_linkit_summary' => '',
        'body_linkit_format' => 'filtered_html',
      ],
    ],
  ];

}
