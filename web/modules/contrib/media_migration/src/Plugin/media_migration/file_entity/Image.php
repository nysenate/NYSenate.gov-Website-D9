<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Image media migration plugin for local image media entities.
 *
 * @FileEntityDealer(
 *   id = "image",
 *   types = {"image"},
 *   destination_media_type_id_base = "image",
 *   destination_media_source_plugin_id = "image"
 * )
 */
class Image extends FileBase {

  /**
   * The map of the alt and title properties and their corresponding field name.
   *
   * @var string[]
   */
  const PROPERTY_FIELD_NAME_MAP = [
    'alt' => 'field_file_image_alt_text',
    'title' => 'field_file_image_title_text',
  ];

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {
    parent::alterMediaEntityMigrationDefinition($migration_definition, $connection);
    $source_field_name = $this->getDestinationMediaSourceFieldName();
    $migration_definition['process'][$source_field_name . '/width'] = 'width';
    $migration_definition['process'][$source_field_name . '/height'] = 'height';
    $migration_definition['process']['thumbnail/target_id'] = 'fid';
    $migration_definition['process']['thumbnail/width'] = 'width';
    $migration_definition['process']['thumbnail/height'] = 'height';

    // These property fields only exist when the file_entity module is
    // installed on the source site.
    foreach (static::PROPERTY_FIELD_NAME_MAP as $property => $field_name) {
      $migration_definition['process']["{$property}_from_media"] = [
        [
          'plugin' => 'skip_on_empty',
          'source' => $field_name,
          'method' => 'process',
        ],
        [
          'plugin' => 'extract',
          'index' => ['0', 'value'],
          // It is impossible to set 'NULL' as default value. Using
          // ['default => NULL'] is equal with not setting the key at all (so
          // every image media migration that should be migrated from an image
          // field will be skipped). Setting the default to a predefined
          // constant that equals to NULL doesn't work either. For example, if
          // we set 'default' to 'constants/alt_and_title_default', then every
          // image that's alt is empty will be migrated with the
          // "constants/alt_and_title_default" string set as alt property. If we
          // don't set 'default' at all, we will get a 'Notice: Undefined index:
          // default_value' exception for empty source values.
          // @todo Revisit after https://www.drupal.org/node/3133516 is
          //   fixed.
          'default' => '',
        ],
        [
          'plugin' => 'default_value',
          'default_value' => NULL,
        ],
      ];

      $property_process = [
        [
          'plugin' => 'null_coalesce',
          'source' => [
            $property,
            "@{$property}_from_media",
          ],
          'default_value' => NULL,
        ],
      ];

      $migration_definition['process'][$source_field_name . '/' . $property] =
      $migration_definition['process']['thumbnail/' . $property] =
        $property_process;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaEntityRow(Row $row, Connection $connection): void {
    parent::prepareMediaEntityRow($row, $connection);
    $file_id = $row->getSourceProperty('fid');
    // Add width and height source properties for image entities. These
    // properties only exist when the file_entity module is installed on the
    // source site.
    $width_and_height_statement = $connection->select('file_metadata', 'fmd')
      ->fields('fmd', ['name', 'value'])
      ->condition('fmd.fid', $file_id)
      ->condition('fmd.name', ['width', 'height'], 'IN')
      ->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($width_and_height_statement as $result_row) {
      $row->setSourceProperty($result_row['name'], unserialize($result_row['value']));
    }

    // Add alt and title properties from image type fields where the current
    // image is the file value.
    foreach ($this->getImageData($connection, $file_id) as $data_key => $data_value) {
      $row->setSourceProperty($data_key, $data_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldStorageRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldStorageRow($row, $connection);
    $settings = $row->getSourceProperty('settings');
    $settings['display_field'] = FALSE;
    $settings['display_default'] = FALSE;
    $row->setSourceProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldInstanceRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldInstanceRow($row, $connection);

    // Alt and title properties of an image field always exist, regardless
    // of whether they are accessible and editable on the entity edit form or
    // not.
    // But we also want to make sure that if either the alt or title property
    // was editable on the source site, then it will be editable also on the
    // destination site.
    // For first, we try to determine which properties should be enabled (and
    // thus accessible and editable on UI) based on the source media image
    // entities' alt and title fields' content. We assume that if there is at
    // least a single value for an image's title property in the source
    // database, then we have to change visibility of the title property to
    // TRUE, so that it can be edited on the destination site as well.
    $alt_title_config = $this->getImageAltTitleSettingsFromPropertyFieldContent($connection);
    // All image field content is also migrated into a media entity. If any of
    // the alt or title properties shouldn't be necessarily shown (and be
    // editable) on the entity edit form based on the source media entity's
    // alt or title field value, we still have to check the configuration of
    // the source site's image fields.
    // If any of the preexisting image field was configured to show the alt or
    // the title property, then we will make their input field visible.
    $props = array_map(function (string $property) {
      return "{$property}_field";
    }, array_keys(static::PROPERTY_FIELD_NAME_MAP));
    if (!empty(array_diff($props, array_keys($alt_title_config)))) {
      $alt_title_config += $this->getSettingsFromImageFields($connection);
    }

    // Get the 'required' settings from the image fields we found.
    $this->mergePropertyRequiredSettingsFromImageFields($alt_title_config, $connection);

    // Add the discovered and the default configuration.
    $additional_properties = $alt_title_config + [
      'alt_field' => TRUE,
      'alt_field_required' => TRUE,
      'title_field' => FALSE,
      'title_field_required' => FALSE,
    ];
    $settings = $additional_properties + ($row->getSourceProperty('settings') ?? []);

    $row->setSourceProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldFormatterRow($row, $connection);
    $options = $row->getSourceProperty('options') ?? [];
    $options['settings'] = [
      'image_style' => 'large',
    ];
    $row->setSourceProperty('options', $options);
  }

  /**
   * Discovers the image field settings based on existing property values.
   *
   * The alt and title properties of an image field always exist, regardless
   * of whether they are actually accessible and editable on the entity edit
   * form or not. This method checks the content of the corresponding alt and
   * title fields. If we find at least a single, non-empty row, we say that the
   * actual property should be shown on the destination media entity's edit
   * form.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   *
   * @return true[]
   *   An array of those field instance settings that should be revealed, keyed
   *   by the settings key ("alt_field", "title_field").
   *   For example, if we have rows for alt, but don't have any data for title,
   *   the array returned will be this:
   *   @code
   *   [
   *     "alt_field" => TRUE
   *   ]
   *   @endcode
   */
  protected function getImageAltTitleSettingsFromPropertyFieldContent(Connection $connection): array {
    $data = [];

    foreach (static::PROPERTY_FIELD_NAME_MAP as $property => $field_name) {
      if (!$connection->schema()->tableExists("field_data_$field_name")) {
        continue;
      }
      $property_values_query = $connection->select("field_data_$field_name", $field_name)
        ->fields($field_name)
        ->condition("$field_name.{$field_name}_value", '', '<>')
        ->isNotNull("$field_name.{$field_name}_value");
      $property_values_present = (int) $property_values_query->countQuery()->execute()->fetchField() > 0;

      if ($property_values_present) {
        $data["{$property}_field"] = TRUE;
      }
    }

    return $data;
  }

  /**
   * Discovers enabled properties based on the image field configurations.
   *
   * The alt and title properties of an image field always exist, regardless
   * of that they are actually accessible and editable on the entity edit form.
   * This method checks the config of every image field's instance configuration
   * When alt (or title) was enabled for at least one image field, we say that
   * the property should be shown on the destination media entity's edit
   * form.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   *
   * @return array
   *   An array of those settings that should be revealed, keyed by the settings
   *   key ('alt_field', 'title_field').
   */
  protected function getSettingsFromImageFields(Connection $connection): array {
    $data = [];
    $image_field_names = $this->getImageFieldData($connection);

    foreach ($image_field_names as $image_field_name) {
      $field_instance_config_results = $connection->select('field_config_instance', 'fci')
        ->fields('fci', ['data'])
        ->condition('fci.field_name', $image_field_name)
        ->condition('fci.entity_type', 'file', '<>')
        ->execute()
        ->fetchAll();

      foreach ($field_instance_config_results as $field_instance_config_result) {
        $field_config_data = unserialize($field_instance_config_result->data);
        $props = array_map(function (string $property) {
          return "{$property}_field";
        }, array_keys(static::PROPERTY_FIELD_NAME_MAP));

        foreach ($props as $property) {
          if (isset($field_config_data['settings'][$property]) && !empty($field_config_data['settings'][$property])) {
            $data[$property] = TRUE;
          }
        }

        if (empty(array_diff($props, array_keys($data)))) {
          break 2;
        }
      }
    }

    return $data;
  }

  /**
   * Merges alt/title 'required' settings based on image field discovery.
   *
   * If any image field have alt (or title) set up as optional, we don't let
   * them being required.
   *
   * @param array $data
   *   The discovered data about alt and title revealment.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  protected function mergePropertyRequiredSettingsFromImageFields(array &$data, Connection $connection): void {
    foreach (static::PROPERTY_FIELD_NAME_MAP as $property => $field_name) {
      $property_field_config_result = $connection->select('field_config_instance', 'fci')
        ->fields('fci', ['data'])
        ->condition('fci.field_name', $field_name)
        ->condition('fci.bundle', 'image')
        ->condition('fci.entity_type', 'file')
        ->execute()
        ->fetchAll();

      if (empty($property_field_config_result)) {
        continue;
      }

      assert(count($property_field_config_result) === 1);

      if ($property_field_config_data = unserialize($property_field_config_result[0]->data)) {
        if (isset($property_field_config_data['required'])) {
          $not_set_or_not_required = !isset($data["{$property}_field_required"]) || empty($data["{$property}_field_required"]);
          $data["{$property}_field_required"] = $not_set_or_not_required && !empty($data["{$property}_field"]) && !empty($property_field_config_data['required']);
        }
      }
    }
  }

}
