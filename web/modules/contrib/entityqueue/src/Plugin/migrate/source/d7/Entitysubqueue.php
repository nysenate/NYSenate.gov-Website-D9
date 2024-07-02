<?php

namespace Drupal\entityqueue\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Fetches entitysubqueues from the source database.
 *
 * @MigrateSource(
 *   id = "d7_entitysubqueue",
 *   source_module = "entityqueue",
 * )
 */
class Entitysubqueue extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('entityqueue_subqueue', 'es')
      ->fields('es', [
        'subqueue_id',
        'queue',
        'name',
        'label',
        'language',
        'uid',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['subqueue_id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'subqueue_id' => $this->t('Primary Key: Unique subqueue ID.'),
      'queue' => $this->t('The queue (bundle) of this subqueue.'),
      'name' => $this->t('The machine-readable name of this subqueue.'),
      'label' => $this->t('The human-readable name of this subqueue.'),
      'language' => $this->t('The {languages}.language of this subqueue.'),
      'uid' => $this->t('The {users}.uid who created this subqueue.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $query = $this->select('field_data_eq_node', 'fden')
      ->fields('fden', ['eq_node_target_id'])
      ->condition('bundle', $row->getSourceProperty('queue'))
      ->condition('entity_id', $row->getSourceProperty('subqueue_id'));
    $ids = $query->execute()->fetchCol();
    $items = [];
    foreach ($ids as $id) {
      $items[] = [
        'target_id' => $id,
      ];
    }

    $row->setSourceProperty('items', $items);
  }

}
