<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Core\Site\Settings;
use Drupal\media_migration\MediaMigration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for Media Migration kernel tests.
 */
abstract class MediaMigrationTestBase extends MigrateDrupalTestBase {

  use MediaMigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function loadFixture($path) {
    if (!empty($path)) {
      parent::loadFixture($path);
    }
  }

  /**
   * Whether an extra managed file with 'undefined' type should be inserted.
   *
   * @var bool
   */
  protected $withExtraManagedFile101 = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getFixtureFilePath());
    $module_handler = \Drupal::moduleHandler();

    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    if ($module_handler->moduleExists('node')) {
      $this->installEntitySchema('node');
      $this->installSchema('node', 'node_access');
    }
    if ($module_handler->moduleExists('embed')) {
      $this->installEntitySchema('embed_button');
    }
    if ($module_handler->moduleExists('media')) {
      $this->installEntitySchema('media');
    }
    if ($module_handler->moduleExists('comment')) {
      $this->installEntitySchema('comment');
      $this->installSchema('comment', 'comment_entity_statistics');
    }
    $this->installSchema('media_migration', MediaMigration::MEDIA_UUID_PROPHECY_TABLE);

    if ($this->withExtraManagedFile101) {
      // Add a plain image.
      $this->sourceDatabase->insert('file_managed')
        ->fields([
          'fid' => 101,
          'uid' => 1,
          'filename' => 'another-yellow.webp',
          'uri' => 'public://yellow_0.webp',
          'filemime' => 'image/webp',
          'filesize' => 3238,
          'status' => 1,
          'timestamp' => 1600000000,
          'type' => 'undefined',
        ])
        ->execute();
    }
  }

  /**
   * Changes the entity embed token transform destination filter plugin.
   *
   * @param string $new_filter_plugin_id
   *   The new token transform destination plugin ID.
   */
  protected function setEmbedTokenDestinationFilterPlugin($new_filter_plugin_id) {
    $current_filter_plugin_id = MediaMigration::getEmbedTokenDestinationFilterPlugin();

    if ($new_filter_plugin_id !== $current_filter_plugin_id) {
      $this->setSetting(MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_SETTINGS, $new_filter_plugin_id);
    }
  }

  /**
   * Sets the method of the embed media reference.
   *
   * @param string $new_reference_method
   *   The reference method to set. This can be 'id', or 'uuid'.
   */
  protected function setEmbedMediaReferenceMethod($new_reference_method) {
    $current_method = Settings::get(MediaMigration::MEDIA_REFERENCE_METHOD_SETTINGS);

    if ($current_method !== $new_reference_method) {
      $this->setSetting(MediaMigration::MEDIA_REFERENCE_METHOD_SETTINGS, $new_reference_method);
    }
  }

  /**
   * Sets the type of the node migration.
   *
   * @param bool $classic_node_migration
   *   Whether nodes should be migrated with the 'classic' way. If this is
   *   FALSE, and the current Drupal instance has the 'complete' migration, then
   *   the complete node migration will be used.
   */
  protected function setClassicNodeMigration(bool $classic_node_migration) {
    $current_method = Settings::get('migrate_node_migrate_type_classic', FALSE);

    if ($current_method !== $classic_node_migration) {
      $this->setSetting('migrate_node_migrate_type_classic', $classic_node_migration);
    }
  }

  /**
   * Executes migrations of the media source database.
   *
   * @param bool $classic_node_migration
   *   Whether the classic node migration has to be executed or not.
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

    $this->executeMediaConfigurationMigrations();

    $this->executeMigrations([
      'd7_view_modes',
      'd7_field',
      'd7_comment_type',
      'd7_node_type',
      'd7_field_instance',
      'd7_field_formatter_settings',
      'd7_field_instance_widget_settings',
      'd7_embed_button_media',
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
    ]);
    $this->executeMigrations(array_filter([
      'd7_file_entity',
      $this->withExtraManagedFile101 ? 'd7_file_plain' : NULL,
      $classic_node_migration ? 'd7_node' : 'd7_node_complete',
    ]));
  }

  /**
   * Executes the media configuration migrations (types, fields etc).
   */
  protected function executeMediaConfigurationMigrations() {
    $this->executeMigrations(array_filter([
      // @todo Every migration that uses the "media_wysiwyg_filter" process
      // plugin should depend on "d7_media_view_modes".
      'd7_media_view_modes',
      $this->withExtraManagedFile101 ? 'd7_file_plain_source_field' : NULL,
      $this->withExtraManagedFile101 ? 'd7_file_plain_type' : NULL,
      'd7_file_entity_source_field',
      'd7_file_entity_type',
      $this->withExtraManagedFile101 ? 'd7_file_plain_source_field_config' : NULL,
      'd7_file_entity_source_field_config',
      $this->withExtraManagedFile101 ? 'd7_file_plain_formatter' : NULL,
      'd7_file_entity_formatter',
      $this->withExtraManagedFile101 ? 'd7_file_plain_widget' : NULL,
      'd7_file_entity_widget',
    ]));
  }

  /**
   * {@inheritdoc}
   *
   * Executes the given migrations without calling assertions in closures.
   */
  protected function executeMigrations(array $ids) {
    $manager = $this->container->get('plugin.manager.migration');
    assert($manager instanceof MigrationPluginManagerInterface);
    foreach ($ids as $id) {
      $instances = $manager->createInstances($id);
      $this->assertNotEmpty($instances, sprintf("No migrations created for id '%s'.", $id));
      foreach ($instances as $instance) {
        $this->executeMigration($instance->id());
      }
    }
  }

}
