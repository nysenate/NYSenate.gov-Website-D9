<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * File Plain Type source plugin.
 *
 * @MigrateSource(
 *   id = "d7_file_plain_type",
 *   source_module = "file"
 * )
 */
class FilePlainType extends FilePlainConfigSourceBase {

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

    $dealer_plugin->prepareMediaTypeRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

}
