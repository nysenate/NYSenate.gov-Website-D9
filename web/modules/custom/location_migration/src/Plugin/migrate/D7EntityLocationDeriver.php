<?php

namespace Drupal\location_migration\Plugin\migrate;

use Drupal\location_migration\LocationMigration;

/**
 * Derives geolocation field config migrations of Drupal 7 entity locations.
 *
 * This deriver class derives field storage, field instance and field widget
 * migrations of those fields which are required to store the "entity location"
 * data – which wasn't stored in a Drupal 7 location field.
 */
class D7EntityLocationDeriver extends LocationDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Using the same derivative for field storage and field instance
    // migrations. Field storage migrations are always derived by their parent
    // entity type. Field instance migrations are derived by parent entity type
    // and by bundle. For bundle-less entities (like user), the bundle will be
    // the entity type, like "d7_entity_location_instance:user:user".
    foreach (static::getDerivatives($base_plugin_definition) as $derivative_id => $values) {
      [
        'entity_type' => $entity_type,
        'bundle' => $bundle,
      ] = $values;
      $derivative_definition = $base_plugin_definition;
      $derivative_definition['source']['entity_type'] = $entity_type;
      $derivative_definition['migration_tags'][] = LocationMigration::ENTITY_LOCATION_MIGRATION_TAG;

      if ($bundle !== NULL) {
        $derivative_definition['source']['bundle'] = $bundle;
      }

      // Process dependencies.
      $migration_required_deps = $derivative_definition['migration_dependencies']['required'] ?? [];
      $storage_migration_dep_key = array_search('d7_entity_location_field', $migration_required_deps);
      if ($storage_migration_dep_key !== FALSE) {
        LocationMigration::mergeDerivedRequiredDependencies(
          $derivative_definition['migration_dependencies'],
          ['d7_entity_location_field'],
          [$entity_type]
        );
      }

      $instance_migration_dep_key = array_search('d7_entity_location_field_instance', $migration_required_deps);
      if ($instance_migration_dep_key !== FALSE) {
        LocationMigration::mergeDerivedRequiredDependencies(
          $derivative_definition['migration_dependencies'],
          ['d7_entity_location_field_instance'],
          array_filter([$entity_type, $bundle])
        );
      }

      if ($bundle) {
        switch ($entity_type) {
          case 'node':
            $derivative_definition['migration_dependencies']['required'][] = 'd7_node_type';
            break;

          case 'taxonomy_term':
            $derivative_definition['migration_dependencies']['required'][] = 'd7_taxonomy_vocabulary';
            break;
        }
      }

      $this->applyDerivativeLabel($derivative_definition);
      $this->derivatives[$derivative_id] = $derivative_definition;
    }

    return $this->derivatives;
  }

}
