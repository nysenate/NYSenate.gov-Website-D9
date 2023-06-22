<?php

namespace Drupal\nys_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'chapters' source plugin.
 *
 * @MigrateSource(
 *   id = "chapters",
 *   source_module = "nys_migrate"
 * )
 */
class Chapters extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $content_types = [
      'article',
      'student_program',
    ];
    $query = $this->select('node', 'n');
    $query->join('field_data_field_chapters', 'fc', 'fc.entity_id = n.nid');
    $query->fields(
          'n', [
            'nid',
            'title',
          ]
      )
      ->condition('bundle', $content_types, 'IN');
    $query->groupBy('n.nid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('NID'),
      'title' => $this->t('Title'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Fetch nid from row.
    $node_nid = $row->getSourceProperty('nid');

    // Fetch all chapters referenced by node.
    $chapter_nids = $this->select('field_data_field_chapters', 'fc')
      ->fields('fc', ['field_chapters_target_id'])
      ->condition('entity_id', $node_nid)
      ->execute()->fetchCol();

    // Clean up results.
    $chapter_refs = [];
    foreach ($chapter_nids as $chapter) {
      $chapter_refs[]['value'] = $chapter;
    }

    // Add results to row.
    $row->setSourceProperty('chapter_nids', $chapter_refs);
    return parent::prepareRow($row);
  }

}
