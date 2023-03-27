<?php

namespace Drupal\media_migration\Utility;

use Drupal\Core\Plugin\PluginBase;
use Drupal\media_migration\MediaMigration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Utility for filtering and manipulating migration plugin definitions.
 *
 * @ingroup utility
 */
class MigrationPluginTool {

  /**
   * Discovers all media entity migrations provided my Media Migration.
   *
   * @return string[]
   *   List of media entity migration IDs.
   */
  public static function getMediaEntityMigrationIds(): array {
    static $ids;

    if (!isset($ids)) {
      $manager = \Drupal::service('plugin.manager.migration');
      assert($manager instanceof MigrationPluginManagerInterface);

      $media_migrations = array_filter(
        $manager->getDefinitions(),
        function (array $definition) {
          return $definition['destination']['plugin'] === 'entity:media' &&
            in_array(MediaMigration::MIGRATION_TAG_CONTENT, $definition['migration_tags'] ?? [], TRUE);
        }
      );

      $ids = array_keys($media_migrations);
    }

    return $ids;
  }

  /**
   * Finds and returns the content entity migrations from the given migrations.
   *
   * @param array $migrations
   *   The array of migration plugins.
   * @param string $destination_entity_type_id
   *   The ID of the destination entity type.
   * @param bool $exclude_custom_migrations
   *   Whether migrations managed by Migrate Plus should be excluded or not.
   *   Defaults to TRUE.
   *
   * @return array
   *   An array of content entity migration plugin definitions, keyed by their
   *   migration plugin ID.
   */
  public static function getContentEntityMigrations(array $migrations, string $destination_entity_type_id, bool $exclude_custom_migrations = TRUE) :array {
    $entity_destination_plugins = [
      'entity',
      'entity_revision',
      'entity_complete',
      'entity_reference_revisions',
    ];

    return array_filter($migrations, function (array $migration, string $migration_id) use ($entity_destination_plugins, $destination_entity_type_id, $exclude_custom_migrations) {
      // If this is not a Drupal 7 migration, we can skip processing it.
      if (!in_array('Drupal 7', $migration['migration_tags'] ?? [])) {
        return FALSE;
      }

      $destination_parts = explode(PluginBase::DERIVATIVE_SEPARATOR, $migration['destination']['plugin']);
      if (
        count($destination_parts) !== 2 ||
        !in_array($destination_parts[0], $entity_destination_plugins)
      ) {
        return FALSE;
      }

      if ($exclude_custom_migrations) {
        // Exclude migrations instantiated by Migrate Plus. These migrations do
        // have UUID, but don't have 'provider', and their ID begins with
        // 'migration_config_deriver:'.
        // @see \Drupal\migrate_plus\Plugin\MigrationConfigDeriver
        if (
          isset($migration['uuid']) &&
          strpos($migration_id, 'migration_config_deriver' . PluginBase::DERIVATIVE_SEPARATOR) === 0
        ) {
          return FALSE;
        }
      }

      if ($destination_parts[1] === $destination_entity_type_id) {
        return TRUE;
      }

      return FALSE;
    }, ARRAY_FILTER_USE_BOTH);
  }

}
