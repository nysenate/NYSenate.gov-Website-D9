<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Drupal 7 media source field instances source based on source database.
 *
 * @MigrateSource(
 *   id = "d7_file_entity_source_field_instance",
 *   source_module = "file_entity"
 * )
 */
class FileEntitySourceFieldInstance extends FileEntityConfigSourceBase {

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
    $rows = [];
    foreach ($results as $result) {
      [
        'type' => $type,
        'scheme' => $scheme,
        'file_extension' => $extension,
      ] = $result;

      if (!($dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme))) {
        continue;
      }
      $destination_media_type_id = $dealer_plugin->getDestinationMediaTypeId();
      $source_values = $rows[$destination_media_type_id] ?? $result + [
        'types' => $type,
        'schemes' => $scheme,
        'file_extensions' => $extension,
      ];

      $source_values['types'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['types']), [$type])));
      $source_values['schemes'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['schemes']), [$scheme])));
      $source_values['file_extensions'] = implode(static::MULTIPLE_SEPARATOR, array_unique(array_merge(explode(static::MULTIPLE_SEPARATOR, $source_values['file_extensions']), [$extension])));
      $source_values['bundle'] = $destination_media_type_id;
      $source_values['bundle_label'] = $dealer_plugin->getDestinationMediaTypeLabel();
      $source_values['source_plugin_id'] = $dealer_plugin->getDestinationMediaSourcePluginId();
      $source_values['source_field_name'] = $dealer_plugin->getDestinationMediaSourceFieldName();
      $source_values['source_field_label'] = $dealer_plugin->getDestinationMediaTypeSourceFieldLabel();
      unset($source_values['type']);
      unset($source_values['scheme']);
      unset($source_values['extension']);
      $rows[$destination_media_type_id] = $source_values;
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'file_extensions' => $this->t('The allowed extensions of a media source field which base type is file, separated by "::"'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['types']['type'] = 'string';
    $ids['schemes']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'types' => $types,
      'schemes' => $schemes,
    ] = $row->getSource();

    $type = explode(static::MULTIPLE_SEPARATOR, $types)[0];
    $scheme = explode(static::MULTIPLE_SEPARATOR, $schemes)[0];

    if (!($dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme))) {
      return FALSE;
    }

    $dealer_plugin->prepareMediaSourceFieldInstanceRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

}
