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
 *   source_provider = "file",
 *   source_module = "nys_migrate"
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
    if (isset($this->configuration['image_field'])) {
      $table = "field_data_" . $this->configuration['image_field'];

      $alt_alias = $query->join($table, 'image_field', 'f.fid = %alias.' . $this->configuration['image_field'] . '_fid');
      $query->addField($alt_alias, $this->configuration['image_field'] . "_alt", 'alt');
      $query->addField($alt_alias, $this->configuration['image_field'] . "_title", 'title');
      $query->addField($alt_alias, $this->configuration['image_field'] . "_width", 'width');
      $query->addField($alt_alias, $this->configuration['image_field'] . "_height", 'height');
    }

    // Get the file description, if configured.
    if (isset($this->configuration['file_field'])) {
      $table = "field_data_" . $this->configuration['file_field'];
      $description_alias = $query->join($table, 'file_field', 'f.fid = %alias.' . $this->configuration['file_field'] . '_fid');
      $query->addField($description_alias, $this->configuration['file_field'] . "_description", 'description');
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
      'description' => $this->t('The description of the file (if present)'),
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
