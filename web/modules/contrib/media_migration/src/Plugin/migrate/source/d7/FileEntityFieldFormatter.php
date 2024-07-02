<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Drupal 7 media field formatter settings source based on source database.
 *
 * @MigrateSource(
 *   id = "d7_file_entity_field_formatter",
 *   source_module = "file_entity"
 * )
 */
class FileEntityFieldFormatter extends FileEntityConfigSourceBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    // Media Migration wants to hide "created", "name", "thumbnail" and "uid"
    // base fields for the default view mode.
    // @see \Drupal\media\Entity\Media
    $iterator = parent::initializeIterator();
    $rows = [];
    foreach ($iterator->getArrayCopy() as $item) {
      [
        'source_field_name' => $source_field_name,
      ] = $item;

      $field_names = [
        $source_field_name => FALSE,
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ];

      foreach ($field_names as $field_name => $hidden) {
        $rows[] = [
          'field_name' => $field_name,
          'hidden' => $hidden,
        ] + $item;
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'types' => $types,
      'schemes' => $schemes,
      'source_field_name' => $source_field_name,
      'field_name' => $field_name,
    ] = $row->getSource();

    if ($field_name === $source_field_name) {
      $type = explode(static::MULTIPLE_SEPARATOR, $types)[0];
      $scheme = explode(static::MULTIPLE_SEPARATOR, $schemes)[0];

      if (!($dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme))) {
        return FALSE;
      }

      $dealer_plugin->prepareMediaSourceFieldFormatterRow($row, $this->getDatabase());
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('Name of the field.'),
      'options' => $this->t('Configuration options of the source field widget.'),
      'hidden' => $this->t('Whether the field is hidden or not.'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'field_name' => ['type' => 'string'],
    ] + parent::getIds();
  }

}
