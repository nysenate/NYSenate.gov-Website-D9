<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Drupal 7 media source field instances source based on source database.
 *
 * @MigrateSource(
 *   id = "d7_file_plain_source_field_storage",
 *   source_module = "file"
 * )
 */
class FilePlainSourceFieldStorage extends FilePlainConfigSourceBase {

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

    $dealer_plugin->prepareMediaSourceFieldStorageRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

}
