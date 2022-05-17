<?php

namespace Drupal\location_migration;

use Drupal\Core\Plugin\PluginBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Constants and migration-related helper functions for location migrations.
 *
 * @internal
 */
final class LocationMigration {

  /**
   * Tag for migration plugin definitions that are already processed.
   *
   * @var string
   */
  const LOCATION_MIGRATION_ALTER_DONE = 'Processed by Location Migration';

  /**
   * Migration tag for migrations of locations that aren't stored in a field.
   *
   * @var string
   */
  const ENTITY_LOCATION_MIGRATION_TAG = 'Entity Location';

  /**
   * Migration tag for migrations related to location field migrations.
   *
   * @var string
   */
  const FIELD_LOCATION_MIGRATION_TAG = 'Location Field';

  /**
   * Prefix of the address field's label.
   *
   * @var string
   */
  const ADDRESS_FIELD_LABEL_PREFIX = 'Address';

  /**
   * Suffix added to the geolocation field.
   *
   * @var string
   */
  const GEOLOCATION_FIELD_NAME_SUFFIX = '_geoloc';

  /**
   * Prefix of the geolocation field's label.
   *
   * @var string
   */
  const GEOLOCATION_FIELD_LABEL_PREFIX = 'Geolocation';

  /**
   * Suffix added to the email field.
   *
   * @var string
   */
  const EMAIL_FIELD_NAME_SUFFIX = '_email';

  /**
   * Prefix of the email field's label.
   *
   * @var string
   */
  const EMAIL_FIELD_LABEL_PREFIX = 'Email';

  /**
   * Suffix added to the phone field.
   *
   * @var string
   */
  const PHONE_FIELD_NAME_SUFFIX = '_phone';

  /**
   * Prefix of the phone field's label.
   *
   * @var string
   */
  const PHONE_FIELD_LABEL_PREFIX = 'Telephone number';

  /**
   * Suffix added to the fax field.
   *
   * @var string
   */
  const FAX_FIELD_NAME_SUFFIX = '_fax';

  /**
   * Prefix of the fax field's label.
   *
   * @var string
   */
  const FAX_FIELD_LABEL_PREFIX = 'Fax number';

  /**
   * Suffix added to the "www" field.
   *
   * @var string
   */
  const WWW_FIELD_NAME_SUFFIX = '_url';

  /**
   * Prefix of the "www" field's label.
   *
   * @var string
   */
  const WWW_FIELD_LABEL_PREFIX = 'Link';

  /**
   * Returns the "base" field name for entity location fields.
   *
   * The returned value is used as the final name for the new "address" field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|int $cardinality
   *   The cardinality of the field. If this bigger than 1, then a suffix will
   *   be added.
   *
   * @return string
   *   The "base" for the entity location field's name.
   */
  public static function getEntityLocationFieldBaseName(string $entity_type_id, $cardinality = 1) {
    $pieces = [
      'location',
      $entity_type_id,
    ];
    if (intval($cardinality) > 1) {
      $pieces[] = (string) $cardinality;
    }

    return implode('_', $pieces);
  }

  /**
   * Merges derivative migration dependencies.
   *
   * @param array $migration_dependencies
   *   The array of the migration dependencies.
   * @param string[] $base_plugin_ids
   *   An array of base plugin IDs of the required, additional migration
   *   dependencies.
   * @param string[] $derivative_pieces
   *   An array of the derivative pieces.
   */
  public static function mergeDerivedRequiredDependencies(array &$migration_dependencies, array $base_plugin_ids, array $derivative_pieces): void {
    $dependencies_to_add = [];
    $derivative_suffix = implode(PluginBase::DERIVATIVE_SEPARATOR, $derivative_pieces);
    foreach ($base_plugin_ids as $base_plugin_id) {
      $dependencies_to_add[] = implode(PluginBase::DERIVATIVE_SEPARATOR, [
        $base_plugin_id,
        $derivative_suffix,
      ]);
    }

    // Remove non-derived dependencies.
    foreach ($base_plugin_ids as $base_plugin_id) {
      if (($key = array_search($base_plugin_id, $migration_dependencies['required'])) !== FALSE) {
        unset($migration_dependencies['required'][$key]);
      }
    }

    $migration_dependencies['required'] = array_unique(
      array_merge(
        array_values($migration_dependencies['required']),
        $dependencies_to_add
      )
    );
  }

  /**
   * Returns the field name of the additional geolocation field.
   *
   * @param string $field_name
   *   The location field's name in the source.
   *
   * @return string
   *   The field name of the geolocation field.
   */
  public static function getGeolocationFieldName(string $field_name): string {
    return mb_substr($field_name, 0, FieldStorageConfig::NAME_MAX_LENGTH - mb_strlen(LocationMigration::GEOLOCATION_FIELD_NAME_SUFFIX)) . LocationMigration::GEOLOCATION_FIELD_NAME_SUFFIX;
  }

  /**
   * Returns the field name of the extra email field for location email values.
   *
   * @param string $field_name
   *   The location field's name in the source.
   *
   * @return string
   *   The field name of the email field.
   */
  public static function getEmailFieldName(string $field_name): string {
    return mb_substr($field_name, 0, FieldStorageConfig::NAME_MAX_LENGTH - mb_strlen(LocationMigration::EMAIL_FIELD_NAME_SUFFIX)) . LocationMigration::EMAIL_FIELD_NAME_SUFFIX;
  }

  /**
   * Returns the field name of the extra telephone field for location phone.
   *
   * @param string $field_name
   *   The location field's name in the source.
   *
   * @return string
   *   The field name of the phone field.
   */
  public static function getPhoneFieldName(string $field_name): string {
    return mb_substr($field_name, 0, FieldStorageConfig::NAME_MAX_LENGTH - mb_strlen(LocationMigration::PHONE_FIELD_NAME_SUFFIX)) . LocationMigration::PHONE_FIELD_NAME_SUFFIX;
  }

  /**
   * Returns the field name of the extra telephone field for location fax.
   *
   * @param string $field_name
   *   The location field's name in the source.
   *
   * @return string
   *   The field name of the fax field.
   */
  public static function getFaxFieldName(string $field_name): string {
    return mb_substr($field_name, 0, FieldStorageConfig::NAME_MAX_LENGTH - mb_strlen(LocationMigration::FAX_FIELD_NAME_SUFFIX)) . LocationMigration::FAX_FIELD_NAME_SUFFIX;
  }

  /**
   * Returns the field name of the extra link field for location www values.
   *
   * @param string $field_name
   *   The location field's name in the source.
   *
   * @return string
   *   The field name of the "www" field.
   */
  public static function getWwwFieldName(string $field_name): string {
    return mb_substr($field_name, 0, FieldStorageConfig::NAME_MAX_LENGTH - mb_strlen(LocationMigration::WWW_FIELD_NAME_SUFFIX)) . LocationMigration::WWW_FIELD_NAME_SUFFIX;
  }

}
