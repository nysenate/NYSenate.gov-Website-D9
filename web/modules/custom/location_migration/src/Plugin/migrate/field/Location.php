<?php

namespace Drupal\location_migration\Plugin\migrate\field;

use Drupal\location_migration\LocationMigration;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migration process plugin for migrations related to Drupal 7 location fields.
 *
 * @MigrateField(
 *   id = "location",
 *   core = {7},
 *   type_map = {
 *    "location" = "address"
 *   },
 *   source_module = "location_cck",
 *   destination_module = "address"
 * )
 */
class Location extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'location_default' => 'address_default',
      'location_all' => 'address_default',
      'location_map' => 'address_default',
      'location_multiple' => 'address_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'location' => 'address_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    parent::alterFieldInstanceMigration($migration);

    $current_process = $migration->getProcess()['settings'] ?? [];
    $current_process[] = [
      'plugin' => 'location_to_address_field_settings',
    ];

    $migration->setProcessOfProperty('settings', $current_process);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $migration->mergeProcessOfProperty(
          $field_name, [
            'plugin' => 'location_to_address',
            'source' => $field_name,
          ]
      );

    $migration_dependencies = $migration->getMigrationDependencies() + ['required' => []];

    // Address cannot store geographical locations, so we need a separate
    // geolocation field.
    $migration->mergeProcessOfProperty(
          LocationMigration::getGeolocationFieldName($field_name), [
            'plugin' => 'location_to_geolocation',
            'source' => $field_name,
          ]
      );
    // These processes only make sense if the corresponding source (and
    // destination) modules are enabled, but it seems that they do not cause any
    // kind of violations.
    $migration->mergeProcessOfProperty(
          LocationMigration::getEmailFieldName($field_name), [
            'plugin' => 'location_email_to_email',
            'source' => $field_name,
          ]
      );
    $migration->mergeProcessOfProperty(
          LocationMigration::getFaxFieldName($field_name), [
            'plugin' => 'location_fax_to_telephone',
            'source' => $field_name,
          ]
      );
    $migration->mergeProcessOfProperty(
          LocationMigration::getPhoneFieldName($field_name), [
            'plugin' => 'location_phone_to_telephone',
            'source' => $field_name,
          ]
      );
    $migration->mergeProcessOfProperty(
          LocationMigration::getWwwFieldName($field_name), [
            'plugin' => 'location_www_to_link',
            'source' => $field_name,
          ]
      );

    // Add the extra field's migrations as required dependencies.
    LocationMigration::mergeDerivedRequiredDependencies(
          $migration_dependencies,
          ['d7_field_location'],
          [$data['entity_type']]
      );
    LocationMigration::mergeDerivedRequiredDependencies(
          $migration_dependencies,
          ['d7_field_location_instance'],
          [$data['entity_type'], $data['bundle']]
      );
    if ($migration instanceof Migration) {
      $migration->set('migration_dependencies', $migration_dependencies);
    }
  }

}
