<?php

namespace Drupal\queue_unique;

use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Queue\DatabaseQueue;

/**
 * Database queue implementation which only adds unique items.
 */
class UniqueDatabaseQueue extends DatabaseQueue {

  /**
   * The database table name.
   *
   * We need a separate table for unique queues as we use a different schema.
   */
  public const TABLE_NAME = 'queue_unique';

  /**
   * Length of the hash column.
   */
  public const HASH_COL_LENGTH = 48;

  /**
   * The hash identifier string.
   */
  public const HASH_ID = 'sha2';

  /**
   * {@inheritdoc}
   */
  public function doCreateItem($data) {
    try {
      $serialized_data = serialize($data);
      $query = $this->connection->insert(static::TABLE_NAME)
        ->fields([
          'name' => $this->name,
          'data' => $serialized_data,
          'created' => time(),
          // Generate a near-unique value for this data on this queue.
          'hash' => static::hash($this->name, $serialized_data),
        ]);
      return $query->execute();
    }
    catch (IntegrityConstraintViolationException $e) {
      // Assume this is because we have violated the uniqueness constraint.
      // Return FALSE to indicate that no item has been placed on the queue as
      // specified by QueueInterface.
      return FALSE;
    }
  }

  /**
   * Generate a hashed string from a queue name and serialized data.
   *
   * @param string $name
   *   The queue name.
   * @param string $serialized_data
   *   The serialized data.
   *
   * @return string
   *   The hash string.
   */
  public static function hash($name, $serialized_data) {
    $substr_length = static::HASH_COL_LENGTH - strlen(static::HASH_ID);
    return static::HASH_ID . substr(base64_encode(hash('sha512', $name . $serialized_data, TRUE)), 0, $substr_length);
  }

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition() {
    return array_merge_recursive(
      parent::schemaDefinition(),
      // We cannot create a unique key on the data field because it is a blob.
      // Instead, we merge an additional field which should contain a hash
      // of the data and a unique key for this field into the original schema
      // definition. These are used to ensure uniqueness.
      [
        'fields' => [
          'hash' => [
            'type' => 'char',
            'length' => static::HASH_COL_LENGTH,
            'not null' => TRUE,
          ],
        ],
        'unique keys' => [
          'unique' => ['hash'],
        ],
      ]
    );
  }

}
