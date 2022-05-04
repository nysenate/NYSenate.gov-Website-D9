<?php

namespace Drupal\nys_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 file_entity source from database.
 *
 * @MigrateSource(
 *   id = "file_entity",
 *   source_provider = "file"
 * )
 */
class FileEntity extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->condition('f.uri', 'temporary://%', 'NOT LIKE')
      ->orderBy('f.fid');
    if (isset($this->configuration['type'])) {
      $query->condition('f.filemime', $query->escapeLike($this->configuration['type']) . "%", 'LIKE');
    }

    // Get the alt text, if configured.
    if (isset($this->configuration['get_alt'])) {
      $alt_alias = $query->addJoin('left', 'field_data_field_file_image_alt_text', 'alt', 'f.fid = %alias.entity_id');
      $query->addField($alt_alias, 'field_file_image_alt_text_value', 'alt');
    }

    // Get the title text, if configured.
    if (isset($this->configuration['get_title'])) {
      $title_alias = $query->addJoin('left', 'field_data_field_file_image_title_text', 'title', 'f.fid = %alias.entity_id');
      $query->addField($title_alias, 'field_file_image_title_text_value', 'title');
    }

    // Get the width.
    if (isset($this->configuration['get_width'])) {
      $width_alias = $query->addJoin('left', 'field_data_field_image_main', 'width', 'f.fid = %alias.field_image_main_fid');
      $query->addField($width_alias, 'field_image_main_width', 'width');
    }

    // Get the height.
    if (isset($this->configuration['get_height'])) {
      $height_alias = $query->addJoin('left', 'field_data_field_image_main', 'height', 'f.fid = %alias.field_image_main_fid');
      $query->addField($height_alias, 'field_image_main_height', 'height');
    }

    // Filter by scheme(s), if configured.
    if (isset($this->configuration['scheme'])) {
      $schemes = [];
      // Accept either a single scheme, or a list.
      foreach ((array) $this->configuration['scheme'] as $scheme) {
        $schemes[] = rtrim($scheme) . '://';
      }
      $schemes = array_map([$this->getDatabase(), 'escapeLike'], $schemes);

      // The uri LIKE 'public://%' OR uri LIKE 'private://%'.
      $conditions = new Condition('OR');
      foreach ($schemes as $scheme) {
        $conditions->condition('uri', $scheme . '%', 'LIKE');
      }
      $query->condition($conditions);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('file', $row->getSourceProperty('type'))) as $field) {
      $fid = $row->getSourceProperty('fid');
      $row->setSourceProperty($field, $this->getFieldValues('file', $field, $fid));
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'uri' => $this->t('The URI to access the file'),
      'filemime' => $this->t('File MIME Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
      'type' => $this->t('The type of this file.'),
      'alt' => $this->t('Alt text of the file (if present)'),
      'title' => $this->t('Title text of the file (if present)'),
      'width' => $this->t('The width of the file (if present)'),
      'height' => $this->t('The height of the file (if present)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
