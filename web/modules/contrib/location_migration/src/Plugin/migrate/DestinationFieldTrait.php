<?php

namespace Drupal\location_migration\Plugin\migrate;

use Drupal\link\LinkItemInterface;
use Drupal\location_migration\LocationMigration;

/**
 * Trait for Drupal 7 Location to Drupal 9 "exttra" field migration sources.
 */
trait DestinationFieldTrait {

  /**
   * Returns an iterator with additional Drupal 9 Location destination fields.
   *
   * This method assumes that the iterator is generated from a field
   * configuration migration source (field storage, instance, widget or
   * formatter).
   *
   * @param iterable $iterable
   *   The source items as array or an object implementing Traversable.
   *
   * @return \ArrayIterator
   *   An iterator with the extra rows for the additional field sources.
   */
  public function addExtraFieldsToIterator(iterable $iterable) {
    $rows = [];

    foreach ($iterable as $item) {
      // We don't have to keep a row for an address field, because the original
      // rows in "d7_field*" migrations will be mapped to an address field by
      // the Location migration field plugin.
      // @see \Drupal\location_migration\Plugin\migrate\field\Location
      $rows = array_merge(
        $rows,
        $this->getExtraFieldRows($item)
      );
    }

    return new \ArrayIterator($rows);
  }

  /**
   * Returns field migration source rows for the additional fields.
   *
   * @param array[] $migration_source_row
   *   The source row of the address field's migration.
   *
   * @return array[][]
   *   The additional rows for the location fields.
   */
  protected function getExtraFieldRows(array $migration_source_row): array {
    // Let's assume that the destination entity type ID is the same as the
    // source.
    $entity_type_id = $migration_source_row['entity_type'];
    if (!($entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id, FALSE))) {
      return [];
    }
    $field_settings = is_string($migration_source_row['field_storage_data'] ?? $migration_source_row['data'] ?? NULL)
      ? unserialize($migration_source_row['field_storage_data'] ?? $migration_source_row['data'])
      : [];
    $location_settings = $migration_source_row['location_settings'] ?? $field_settings['settings']['location_settings'] ?? [];
    $hidden_form_props = static::getFormHiddenFields($location_settings);
    $hidden_display_props = static::getDisplayHiddenFields($location_settings);
    $field_label_args = [
      '@entity-label' => $entity_type_definition->getSingularLabel(),
    ];
    $base_field_name = $migration_source_row['field_name'] ?? LocationMigration::getEntityLocationFieldBaseName($entity_type_id);
    // Geolocation is our dependency, assuming that its field is always
    // available.
    $display_is_hidden = empty(array_diff([
      'map_link',
      'coords',
    ], $hidden_display_props));
    $widget_is_hidden = empty(array_diff([
      'locpick',
    ], $hidden_form_props));
    $items = [
      [
        'field_name' => LocationMigration::getGeolocationFieldName($base_field_name),
        'type' => 'geolocation',
        'widget_type' => 'geolocation_latlng',
        'formatter_type' => 'geolocation_latlng',
        'field_label' => (string) $this->t('@field-label of @entity-label', $field_label_args + [
          '@field-label' => LocationMigration::GEOLOCATION_FIELD_LABEL_PREFIX,
        ]),
        'display_hidden' => $display_is_hidden,
        'widget_hidden' => $widget_is_hidden,
      ] + $migration_source_row,
    ];
    // Add an extra "email" field.
    if ($this->moduleExists('location_email')) {
      $display_is_hidden = empty(array_diff([
        'email',
      ], $hidden_display_props));
      $widget_is_hidden = empty(array_diff([
        'email',
      ], $hidden_form_props));
      $items[] = [
        'field_name' => LocationMigration::getEmailFieldName($base_field_name),
        'type' => 'email',
        'widget_type' => 'email_default',
        'formatter_type' => 'email_mailto',
        'field_label' => (string) $this->t('@field-label of @entity-label', $field_label_args + [
          '@field-label' => LocationMigration::EMAIL_FIELD_LABEL_PREFIX,
        ]),
        'display_hidden' => $display_is_hidden,
        'widget_hidden' => $widget_is_hidden,
      ] + $migration_source_row;
    }
    // Fax and phone are migrated when the "telephone" field is available.
    $telephone_definition = $this->fieldTypePluginManager->getDefinition('telephone', FALSE);
    if ($telephone_definition && $telephone_definition['provider'] === 'telephone') {
      if ($this->moduleExists('location_fax')) {
        $display_is_hidden = empty(array_diff([
          'fax',
        ], $hidden_display_props));
        $widget_is_hidden = empty(array_diff([
          'fax',
        ], $hidden_form_props));
        $items[] = [
          'field_name' => LocationMigration::getFaxFieldName($base_field_name),
          'type' => 'telephone',
          'widget_type' => 'telephone_default',
          'formatter_type' => 'basic_string',
          'field_label' => (string) $this->t('@field-label of @entity-label', $field_label_args + [
            '@field-label' => LocationMigration::FAX_FIELD_LABEL_PREFIX,
          ]),
          'display_hidden' => $display_is_hidden,
          'widget_hidden' => $widget_is_hidden,
        ] + $migration_source_row;
      }
      if ($this->moduleExists('location_phone')) {
        $display_is_hidden = empty(array_diff([
          'phone',
        ], $hidden_display_props));
        $widget_is_hidden = empty(array_diff([
          'phone',
        ], $hidden_form_props));
        $items[] = [
          'field_name' => LocationMigration::getPhoneFieldName($base_field_name),
          'type' => 'telephone',
          'widget_type' => 'telephone_default',
          'formatter_type' => 'basic_string',
          'field_label' => (string) $this->t('@field-label of @entity-label', $field_label_args + [
            '@field-label' => LocationMigration::PHONE_FIELD_LABEL_PREFIX,
          ]),
          'display_hidden' => $display_is_hidden,
          'widget_hidden' => $widget_is_hidden,
        ] + $migration_source_row;
      }
    }
    // "WWW" is migrated when the "link" field is available.
    $link_definition = $this->fieldTypePluginManager->getDefinition('link', FALSE);
    if (
      $this->moduleExists('location_www') &&
      $link_definition &&
      $link_definition['provider'] === 'link'
    ) {
      $display_is_hidden = empty(array_diff([
        'www',
      ], $hidden_display_props));
      $widget_is_hidden = empty(array_diff([
        'www',
      ], $hidden_form_props));
      $items[] = [
        'field_name' => LocationMigration::getWwwFieldName($base_field_name),
        'type' => 'link',
        'widget_type' => 'link_default',
        'formatter_type' => 'link',
        'field_label' => (string) $this->t('@field-label of @entity-label', $field_label_args + [
          '@field-label' => LocationMigration::WWW_FIELD_LABEL_PREFIX,
        ]),
        'field_instance_settings' => [
          'link_type' => LinkItemInterface::LINK_GENERIC,
          'title' => DRUPAL_DISABLED,
        ],
        'display_hidden' => $display_is_hidden,
        'widget_hidden' => $widget_is_hidden,
      ] + $migration_source_row;
    }

    return $items;
  }

  /**
   * Returns the list of location properties hidden on forms.
   *
   * @param array[] $location_field_configuration
   *   The configuration of the location field.
   *
   * @return array
   *   List of the location properties hidden on forms.
   */
  public static function getFormHiddenFields(array $location_field_configuration): array {
    $default_config = ['collect' => '0'];
    $config = $location_field_configuration['form']['fields'] + [
      'email' => $default_config,
      'fax' => $default_config,
      'phone' => $default_config,
      'www' => $default_config,
    ];
    return array_keys(
      array_filter($config, function ($conf) {
        return $conf['collect'] === '0';
      })
    );
  }

  /**
   * Returns the list of location properties hidden on entity displays.
   *
   * @param array[] $location_field_configuration
   *   The configuration of the location field.
   *
   * @return array
   *   List of the location properties hidden on entity displays.
   */
  public static function getDisplayHiddenFields(array $location_field_configuration): array {
    $config = $location_field_configuration['display']['hide'] + [
      'email' => 0,
      'fax' => 0,
      'phone' => 0,
      'www' => 0,
    ];
    return array_keys(
      array_filter($config, function ($conf) {
        return !empty($conf);
      })
    );
  }

}
