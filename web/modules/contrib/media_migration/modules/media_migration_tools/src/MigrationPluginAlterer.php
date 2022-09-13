<?php

namespace Drupal\media_migration_tools;

use Drupal\media_migration\MediaMigration;

/**
 * Migration plugin alterer for "fixing" migrations provided by Migrate Tools.
 */
class MigrationPluginAlterer {

  /**
   * Alters migrate plugins.
   *
   * @param array $migrations
   *   The array of migration plugins.
   */
  public function alter(array &$migrations) {
    $this->switchIdMapPlugin($migrations);
    $this->addRequirementsKey($migrations);
  }

  /**
   * Re-adds ID map plugin config "smart_sql" which can handle long IDs.
   *
   * This method is required only because migrate_plus does not define the ID
   * map plugin property configuration (or scheme) for its migration entities.
   *
   * @param array $migrations
   *   The array of migration plugins.
   */
  protected function switchIdMapPlugin(array &$migrations) {
    // Collect all derived media migrations.
    $file_to_media_migrations = array_filter($migrations, function (array $migration_definition) {
      $migration_tags = $migration_definition['migration_tags'] ?? [];
      return in_array(MediaMigration::MIGRATION_TAG_MAIN, $migration_tags, TRUE) &&
        !empty($migration_definition['source']['destination_media_type_id']) &&
        empty($migration_definition['idMap']);
    });
    // Re-add the missing ID map plugin configuration.
    foreach ($file_to_media_migrations as $migration_plugin_id => $file_to_media_migration_def) {
      $migrations[$migration_plugin_id]['idMap'] = [
        'plugin' => 'smart_sql',
      ];
    }
  }

  /**
   * Adds a "requirements" key to media migration plugins.
   *
   * This method only required by migrate_tools since it searches migration
   * dependencies in this key.
   *
   * @param array $migrations
   *   The array of migration plugins.
   */
  protected function addRequirementsKey(array &$migrations) {
    foreach ($migrations as $migration_plugin_id => $migration_definition) {
      if (
        empty($migration_definition['migration_tags']) ||
        !in_array(MediaMigration::MIGRATION_TAG_MAIN, $migration_definition['migration_tags'], TRUE) ||
        empty($migration_definition['migration_dependencies']['required'])
      ) {
        continue;
      }

      $migrations[$migration_plugin_id]['requirements'] = $migration_definition['migration_dependencies']['required'];
    }
  }

}
