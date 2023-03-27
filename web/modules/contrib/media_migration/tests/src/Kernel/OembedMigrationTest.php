<?php

namespace Drupal\Tests\media_migration\Kernel;

use Drupal\media\Entity\Media;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Oembed media migration.
 *
 * @group media_migration
 */
class OembedMigrationTest extends MigrateDrupal7TestBase {

  use MigMagKernelTestDxTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'media',
    'migrate_plus',
    'media_migration',
    'media_migration_test_oembed',
    'migmag_process',
    'menu_ui',
    'node',
    'smart_sql_idmap',
    'image',
    'file',
    'text',
    'filter',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('media_migration', ['media_migration_media_entity_uuid_prophecy']);

    $this->createMediaType('oembed:video', ['id' => 'remote_video']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $this->installSchema('file', 'file_usage');

    $this->loadFixture(
      implode(DIRECTORY_SEPARATOR, [
        dirname(__DIR__, 3),
        'tests',
        'fixtures',
        'drupal7_oembed_partial.php',
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      dirname(__DIR__, 3),
      'tests',
      'fixtures',
      'drupal7_media.php',
    ]);
  }

  /**
   * Tests Oembed media migrations.
   */
  public function testMigration(): void {
    $this->startCollectingMessages();

    $this->executeMigration('d7_file');
    $this->executeMigrations([
      'd7_field',
      'd7_view_modes',
      'd7_filter_format',
      'd7_comment_type',
      'd7_node_type',
      'd7_file_entity_source_field',
      'd7_file_entity_type',
      'd7_field_instance',
      'd7_file_entity_source_field_config',
      'd7_user_role',
      'd7_user',
      'd7_file_entity:video',
      'd7_file_entity:image:public',
      'd7_node_complete:oembed_content',
    ]);
    $this->assertExpectedMigrationMessages();

    $media = Media::load(1);
    $this->assertInstanceOf(Media::class, $media);
    $this->assertEquals(
      [
        'mid' => [['value' => '1']],
        'vid' => [['value' => '1']],
        'langcode' => [['value' => 'en']],
        'bundle' => [['target_id' => 'remote_video']],
        'revision_user' => [],
        'revision_log_message' => [],
        'status' => [['value' => '1']],
        'uid' => [['target_id' => '1']],
        'name' => [['value' => 'ACSF']],
        'created' => [['value' => '1648447404']],
        'default_langcode' => [['value' => '1']],
        'revision_default' => [['value' => '1']],
        'revision_translation_affected' => [['value' => '1']],
        'field_media_oembed_video' => [
          [
            'value' => 'https://player.vimeo.com/video/268828727',
          ],
        ],
      ],
      array_diff_key(
        $media->toArray(),
        ['uuid' => 1, 'thumbnail' => 1, 'revision_created' => 1, 'changed' => 1]
      )
    );

    $this->assertCount(1, Node::loadMultiple());
    $this->assertEquals(
      [
        'field_media' => [['target_id' => 1]],
      ],
      array_intersect_key(
        Node::load(83863)->toArray(),
        ['field_media' => 1]
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMigration(MigrationInterface $migration) {
    $source = $migration->getSourceConfiguration();
    if ($source['plugin'] === 'd7_file') {
      $source_file_path = implode(
        DIRECTORY_SEPARATOR,
        [
          dirname(__DIR__, 2),
          'fixtures',
        ]
      );

      $source['constants']['source_base_path'] = $source_file_path;
      $migration->set('source', $source);
    }
  }

}
