<?php

namespace Drupal\Tests\media_migration\Kernel;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests YouTube Field migration.
 *
 * @group media_migration
 */
class YoutubeFieldMigrationTest extends MigrateDrupal7TestBase {

  use MigMagKernelTestDxTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_migration',
    'media_migration_test_oembed',
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
        'drupal7_youtube_field_partial.php',
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
      'drupal7_nomedia.php',
    ]);
  }

  /**
   * Tests YouTube Field media and field value migrations.
   */
  public function testMigration(): void {
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_field',
      'd7_view_modes',
      'd7_filter_format',
      'd7_node_type',
      'd7_field_instance',
      'd7_file',
      'd7_user_role',
      'd7_user',
      'd7_youtube_field',
      'd7_node_complete:youtubecontent',
    ]);
    $this->assertExpectedMigrationMessages();

    $media_entities = Media::loadMultiple();
    $this->assertCount(3, $media_entities);

    $this->assertCount(2, Node::loadMultiple());
    $this->assertEquals(
      [
        'field_youtube_field' => [['target_id' => 1]],
        'field_youtube_link' => [],
      ],
      array_intersect_key(
        Node::load(40)->toArray(),
        ['field_youtube_field' => 1, 'field_youtube_link' => 1]
      )
    );

    $this->assertEquals(
      [
        'field_youtube_field' => [['target_id' => 3]],
        'field_youtube_link' => [['target_id' => 2]],
      ],
      array_intersect_key(
        Node::load(50)->toArray(),
        ['field_youtube_field' => 1, 'field_youtube_link' => 1]
      )
    );
  }

}
