<?php

namespace Drupal\simple_sitemap\Queue;

use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Component\Datetime\Time;

/**
 * Class SimplesitemapQueue
 * @package Drupal\simple_sitemap\Queue
 */
class SimplesitemapQueue extends DatabaseQueue {

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * SimplesitemapQueue constructor.
   * @param $name
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Component\Datetime\Time $time
   */
  public function __construct($name, Connection $connection, Time $time) {
    parent::__construct($name, $connection);
    $this->time = $time;
  }

  /**
   * Overrides \Drupal\Core\Queue\DatabaseQueue::claimItem().
   *
   * Unlike \Drupal\Core\Queue\DatabaseQueue::claimItem(), this method provides
   * a default lease time of 0 (no expiration) instead of 30. This allows the
   * item to be claimed repeatedly until it is deleted.
   */
  public function claimItem($lease_time = 0) {
    try {
      $item = $this->connection->queryRange('SELECT data, item_id FROM {queue} q WHERE name = :name ORDER BY item_id ASC', 0, 1, [':name' => $this->name])->fetchObject();
      if ($item) {
        $item->data = unserialize($item->data);
        return $item;
      }
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }

    return FALSE;
  }

  /**
   * Gets a simple_sitemap queue item in a very efficient way.
   *
   * @return \Generator
   *   A queue item object with at least the following properties:
   *   - data: the same as what what passed into createItem().
   *   - item_id: the unique ID returned from createItem().
   *   - created: timestamp when the item was put into the queue.
   *
   * @see \Drupal\Core\Queue\QueueInterface::claimItem
   */
  public function yieldItem() {
    try {
      $query = $this->connection->query('SELECT data, item_id FROM {queue} q WHERE name = :name ORDER BY item_id ASC', [':name' => $this->name]);
      while ($item = $query->fetchObject()) {
        $item->data = unserialize($item->data);
        yield $item;
      }
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  public function createItems($data_sets) {
    $try_again = FALSE;
    try {
      $id = $this->doCreateItems($data_sets);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if (!$try_again = $this->ensureTableExists()) {
        // If the exception happened for other reason than the missing table,
        // propagate the exception.
        throw $e;
      }
    }
    // Now that the table has been created, try again if necessary.
    if ($try_again) {
      $id = $this->doCreateItems($data_sets);
    }

    return $id;
  }

  protected function doCreateItems($data_sets) {
    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields(['name', 'data', 'created']);

    foreach ($data_sets as $i => $data) {
      $query->values([
        $this->name,
        serialize($data),
        $this->time->getRequestTime(),
      ]);
    }

    return $query->execute();
  }

  public function deleteItems($item_ids) {
    try {
      $this->connection->delete(static::TABLE_NAME)
        ->condition('item_id', $item_ids, 'IN')
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

}
