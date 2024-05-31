<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Drupal 7 media field widget settings source based on source database.
 *
 * @MigrateSource(
 *   id = "d7_file_plain_field_widget",
 *   source_module = "file"
 * )
 */
class FilePlainFieldWidget extends FilePlainConfigSourceBase {

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

    $dealer_plugin->prepareMediaSourceFieldWidgetRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'options' => $this->t('Configuration options of the source field widget.'),
    ] + parent::fields();
  }

}
