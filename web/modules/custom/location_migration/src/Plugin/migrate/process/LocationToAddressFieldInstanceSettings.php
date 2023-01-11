<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process plugin for D7 location field instance settings.
 *
 * This plugin converts D7 location field instance settings to Drupal 9 address
 * field settings.
 *
 * @MigrateProcessPlugin(
 *   id = "location_to_address_field_settings"
 * )
 */
class LocationToAddressFieldInstanceSettings extends LocationProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') === 'location') {
      // It seems that Drupal 7 Location is not able to "require" any of its
      // properties.
      return static::defaultSettings();
    }

    return $value;
  }

  /**
   * Returns the "default" settings for the destination "address" field.
   *
   * @return array
   *   The "default" settings for the destination "address" field.
   */
  public static function defaultSettings() {
    return [
      'available_countries' => [],
      'langcode_override' => '',
      'field_overrides' => [
        'givenName' => ['override' => 'optional'],
        'familyName' => ['override' => 'optional'],
        'addressLine1' => ['override' => 'optional'],
        'postalCode' => ['override' => 'optional'],
        'locality' => ['override' => 'optional'],
        'administrativeArea' => ['override' => 'optional'],
      ],
      'fields' => [],
    ];
  }

}
