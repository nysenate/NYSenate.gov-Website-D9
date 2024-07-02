<?php

namespace Drupal\entityqueue\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Fetches entityqueues from the source database.
 *
 * @MigrateSource(
 *   id = "d7_entityqueue",
 *   source_module = "entityqueue",
 * )
 */
class Entityqueue extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('entityqueue_queue', 'eq')
      ->fields('eq', [
        'name',
        'label',
        'language',
        'handler',
        'target_type',
        'settings',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('The machine-readable name of this queue.'),
      'label' => $this->t('The human-readable name of this queue.'),
      'language' => $this->t('The {languages}.language of this queue.'),
      'handler' => $this->t('The handler plugin that manages this queue.'),
      'target_type' => $this->t('The target entity type of this queue.'),
      'settings' => $this->t('Serialized settings containing the queue properties that do not warrant a dedicated column.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('settings', unserialize($row->getSourceProperty('settings')));
  }

}
