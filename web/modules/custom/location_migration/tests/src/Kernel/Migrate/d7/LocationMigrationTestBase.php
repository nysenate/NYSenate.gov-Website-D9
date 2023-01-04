<?php

namespace Drupal\Tests\location_migration\Kernel\Migrate\d7;

use Drupal\Core\Site\Settings;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for Location Migration's kernel tests.
 */
abstract class LocationMigrationTestBase extends MigrateDrupalTestBase {

  /**
   * Returns the drupal-relative path to the database fixture file.
   *
   * @return string
   *   The path to the database file.
   */
  abstract public function getDatabaseFixtureFilePath(): string;

  /**
   * Returns the absolute path to the file system fixture directory.
   *
   * @return string|null
   *   The absolute path to the file system fixture directory.
   */
  public function getFilesystemFixturePath(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->loadFixture($this->getDatabaseFixtureFilePath());
    $module_handler = \Drupal::moduleHandler();

    if ($module_handler->moduleExists('file')) {
      $this->installEntitySchema('file');
      $this->installSchema('file', 'file_usage');
    }

    if ($module_handler->moduleExists('node')) {
      $this->installSchema('node', 'node_access');
    }

    if ($module_handler->moduleExists('comment')) {
      $this->installSchema('comment', 'comment_entity_statistics');
    }

    // Let's install all default configuration.
    $module_list = array_keys($module_handler->getModuleList());
    $this->installConfig($module_list);
  }

  /**
   * Sets the type of the node migration.
   *
   * @param bool $classic_node_migration
   *   Whether nodes should be migrated with the 'classic' way. If this is
   *   FALSE, and the current Drupal instance has the 'complete' migration, then
   *   the complete node migration will be used.
   */
  protected function setClassicNodeMigration(bool $classic_node_migration): void {
    $current_method = Settings::get('migrate_node_migrate_type_classic', FALSE);

    if ($current_method !== $classic_node_migration) {
      $this->setSetting('migrate_node_migrate_type_classic', $classic_node_migration);
    }
  }

  /**
   * Executes the relevant migrations.
   *
   * @param bool $classic_node_migration
   *   Whether node migrations should be executed with the classic node
   *   migration or not.
   * @param bool $with_entity_locations
   *   Whether entity location migrations should be executed.
   */
  protected function executeRelevantMigrations(bool $classic_node_migration = FALSE, bool $with_entity_locations = TRUE): void {
    // Execute file migrations if fixture path is provided.
    if ($fs_fixture_path = $this->getFilesystemFixturePath()) {
      foreach (['d7_file', 'd7_file_private'] as $file_migration_plugin_id) {
        $file_migration = $this->getMigration($file_migration_plugin_id);
        $source = $file_migration->getSourceConfiguration();
        $source['constants']['source_base_path'] = $fs_fixture_path;
        $file_migration->set('source', $source);
        $this->executeMigration($file_migration);
      }
    }

    // Ignore irrelevant errors.
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_view_modes',
      'd7_field',
      'd7_node_type',
      'd7_field_instance',
      'd7_field_formatter_settings',
      'd7_field_instance_widget_settings',
    ]);
    $this->stopCollectingMessages();

    $this->executeMigrations($with_entity_locations
      ? [
        'd7_taxonomy_vocabulary',
        'd7_entity_location_field:taxonomy_term',
        'd7_entity_location_field_instance:taxonomy_term:vocabulary_1',
        'd7_entity_location_field_widget:taxonomy_term:vocabulary_1',
        'd7_entity_location_field_formatter:taxonomy_term:vocabulary_1',
        'd7_taxonomy_term:vocabulary_1',
      ]
      : [
        'd7_taxonomy_vocabulary',
        'd7_taxonomy_term:vocabulary_1',
      ]
    );

    $this->executeMigrations($with_entity_locations
      ? [
        'd7_user_role',
        'd7_entity_location_field:user',
        'd7_entity_location_field_instance:user:user',
        'd7_entity_location_field_widget:user:user',
        'd7_entity_location_field_formatter:user:user',
        'd7_user',
      ]
      : [
        'd7_user_role',
        'd7_user',
      ]
    );

    $node_migration_base = $classic_node_migration ? 'd7_node' : 'd7_node_complete';
    $this->executeMigrations($with_entity_locations
      ? [
        'd7_field_location:node',
        'd7_field_location_instance:node',
        'd7_entity_location_field:node',
        'd7_entity_location_field_instance:node',
        'd7_entity_location_field_widget:node',
        'd7_entity_location_field_formatter:node',
        $node_migration_base,
        'd7_field_location_widget:node',
        'd7_field_location_formatter:node',
      ]
      : [
        'd7_field_location:node',
        'd7_field_location_instance:node',
        $node_migration_base,
        'd7_field_location_widget:node',
        'd7_field_location_formatter:node',
      ]
    );
  }

}
