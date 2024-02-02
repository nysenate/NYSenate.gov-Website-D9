<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForNonMediaSourceTrait;

/**
 * Base class for Media Migration kernel tests for non-media sources.
 */
abstract class MediaMigrationPlainTestBase extends MediaMigrationTestBase {

  use MediaMigrationAssertionsForNonMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'editor',
    'field',
    'file',
    'filter',
    'image',
    'link',
    'linkit',
    'media',
    'media_migration',
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
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures/drupal7_nomedia.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function executeMediaMigrations(bool $classic_node_migration = FALSE) {
    // The Drupal 8|9 entity revision migration causes a file not found
    // exception without properly migrated files. For this test, it is enough to
    // properly migrate the public files.
    $fs_fixture_path = implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      \Drupal::service('extension.list.module')->getPath('media_migration'),
      'tests',
      'fixtures',
    ]);
    $file_migration = $this->getMigration('d7_file');
    $source = $file_migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs_fixture_path;
    $file_migration->set('source', $source);

    $this->executeMigration($file_migration);
    $this->executeMigrations([
      'd7_view_modes',
      'd7_field',
      'd7_node_type',
      'd7_field_instance',
      'd7_file_plain_source_field',
      'd7_file_plain_type',
      'd7_file_plain_source_field_config',
      'd7_field_formatter_settings',
      'd7_field_instance_widget_settings',
      'd7_file_plain_formatter',
      'd7_file_plain_widget',
      'd7_filter_format',
      // Nodes and media entities need an owner.
      'd7_user_role',
      'd7_user',
      'd7_file_plain',
      $classic_node_migration ? 'd7_node' : 'd7_node_complete',
    ]);
  }

}
