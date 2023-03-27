<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Drupal 7 media source field instances source based on source database.
 *
 * @MigrateSource(
 *   id = "d7_file_plain_source_field_instance",
 *   source_module = "file"
 * )
 */
class FilePlainSourceFieldInstance extends FilePlainConfigSourceBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->addExpression($this->getExtensionExpression(), 'file_extension');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();

    // Add the array of all instances using the same media type to each row.
    $rows = [];
    foreach ($results as $result) {
      [
        'mime' => $mime,
        'scheme' => $scheme,
        'file_extension' => $extension,
      ] = $result;

      if (!($dealer_plugin = $this->fileDealerManager->createInstanceFromSchemeAndMime($scheme, $mime))) {
        continue;
      }

      $destination_media_type_id = $dealer_plugin->getDestinationMediaTypeId();
      $source_values = $rows[$destination_media_type_id] ?? $result + [
        'mimes' => $mime,
        'schemes' => $scheme,
        'file_extensions' => $extension,
      ];

      $source_values['mimes'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['mimes']), [$mime])));
      $source_values['schemes'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['schemes']), [$scheme])));
      $source_values['file_extensions'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_filter(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['file_extensions']), [$extension]))));
      $source_values['bundle'] = $destination_media_type_id;
      $source_values['bundle_label'] = $dealer_plugin->getDestinationMediaTypeLabel();
      $source_values['source_plugin_id'] = $dealer_plugin->getDestinationMediaSourcePluginId();
      $source_values['source_field_name'] = $dealer_plugin->getDestinationMediaSourceFieldName();
      $source_values['source_field_label'] = $dealer_plugin->getDestinationMediaTypeSourceFieldLabel();
      unset($source_values['file_extension']);
      unset($source_values['mime']);
      unset($source_values['scheme']);
      $rows[$destination_media_type_id] = $source_values;
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'mimes' => $mimes,
      'schemes' => $schemes,
    ] = $row->getSource();

    $mime = explode(static::MULTIPLE_SEPARATOR, $mimes)[0];
    $scheme = explode(static::MULTIPLE_SEPARATOR, $schemes)[0];

    if (!($dealer_plugin = $this->fileDealerManager->createInstanceFromSchemeAndMime($scheme, $mime))) {
      return FALSE;
    }

    $dealer_plugin->prepareMediaSourceFieldInstanceRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'file_extensions' => $this->t('List of the enabled file extensions, separated by a whitespace'),
    ] + parent::fields();
  }

}
