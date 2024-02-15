<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * File Entity Type source plugin.
 *
 * @MigrateSource(
 *   id = "d7_file_entity_type",
 *   source_module = "file_entity"
 * )
 */
class FileEntityType extends FileEntityConfigSourceBase {

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

    $dealer_plugin->prepareMediaTypeRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

}
