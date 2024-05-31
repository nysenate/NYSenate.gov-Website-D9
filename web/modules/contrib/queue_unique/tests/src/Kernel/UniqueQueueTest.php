<?php

namespace Drupal\Tests\queue_unique\Kernel;

use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\queue_unique\UniqueDatabaseQueue;

/**
 * Unique queue kernel test.
 *
 * @group queue_unique
 */
class UniqueQueueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['queue_unique'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $this->container->setParameter('install_profile', 'testing');
  }

  /**
   * Test that queues correctly add only unique data.
   */
  public function testQueueIsUnique() {
    $queue_factory = $this->container->get('queue_unique.database');

    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_factory->get('queue');

    self::assertInstanceOf(UniqueDatabaseQueue::class, $queue);

    $examples = [
      1,
      '1',
      ['a' => 1, 'b' => ['x']],
      (object) ['a' => 1, 'b' => ['x']],
    ];
    $other_data = ['z'];

    foreach ($examples as $data) {
      // Add an item to the empty unique queue.
      $item_id = $queue->createItem($data);
      self::assertNotFalse($item_id);
      self::assertEquals(1, $queue->numberOfItems());

      // When we try to add the item again we should not get an item id as the
      // item has not been readded and the number of items on the queue should
      // stay the same.
      $duplicate_id = $queue->createItem($data);
      self::assertFalse($duplicate_id);
      self::assertEquals(1, $queue->numberOfItems());

      // Claim and delete the item from the queue simulating an item being
      // processed.
      $item = $queue->claimItem();
      $queue->deleteItem($item);

      // With the original item being gone we should be able to re-add an item
      // with the same data.
      $item_id = $queue->createItem($data);
      self::assertNotFalse($item_id);
      self::assertEquals(1, $queue->numberOfItems());
      // Using some other data adds a new item.
      $item_id = $queue->createItem($other_data);
      self::assertNotFalse($item_id);
      self::assertEquals(2, $queue->numberOfItems());
      $queue->deleteQueue();
    }
    // Every item in the examples has a different serialized string.
    foreach ($examples as $data) {
      $item_id = $queue->createItem($data);
      self::assertNotFalse($item_id);
    }
    self::assertEquals(count($examples), $queue->numberOfItems());
  }

  /**
   * Test queue_unique_update_8001().
   *
   * @todo Remove this test method and associated code after the update hook
   *  is released so that all md5 references are removed.
   */
  public function testUpdateHook8001() {
    \Drupal::moduleHandler()->loadInclude('queue_unique', 'install');
    /** @var \Drupal\Core\Database\Schema $database_schema */
    $database_schema = $this->container->get('database')->schema();
    // The table should not exist yet.
    self::assertFalse($database_schema->tableExists(UniqueDatabaseQueue::TABLE_NAME));
    $message = queue_unique_update_8001();
    self::assertStringContainsString('Queue table does not exist', $message);
    // If the table exists and is empty, it should be dropped.
    $old_schema_definition = $this->oldSchemaDefinition();
    $database_schema->createTable(UniqueDatabaseQueue::TABLE_NAME, $old_schema_definition);
    self::assertTrue($database_schema->tableExists(UniqueDatabaseQueue::TABLE_NAME));
    $message = queue_unique_update_8001();
    self::assertStringContainsString('Table dropped', $message);
    self::assertFalse($database_schema->tableExists(UniqueDatabaseQueue::TABLE_NAME));
    // Create the table again and populate it.
    $database_schema->createTable(UniqueDatabaseQueue::TABLE_NAME, $old_schema_definition);
    self::assertTrue($database_schema->tableExists(UniqueDatabaseQueue::TABLE_NAME));
    $names = ['testq1', 'myotherq2'];
    $examples = [
      1,
      '1',
      ['a' => 1, 'b' => ['x']],
      (object) ['a' => 1, 'b' => ['x']],
    ];
    foreach ($names as $name) {
      foreach ($examples as $data) {
        $this->createItemMd5($name, $data);
      }
    }
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $this->container->get('database');
    $query = $connection->select(UniqueDatabaseQueue::TABLE_NAME)->countQuery();
    $count = $query->execute()->fetchField();
    self::assertEquals(count($names) * count($examples), $count);
    $message = queue_unique_update_8001();
    $expected = "Migrated $count items from the old queue table to the new table.";
    self::assertEquals($expected, $message);
    $queue_factory = $this->container->get('queue_unique.database');
    foreach ($names as $name) {
      /* @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $queue_factory->get($name);
      self::assertEquals(count($examples), $queue->numberOfItems());
      foreach ($examples as $data) {
        // If the new hash was calculated correctly by the update, duplicated
        // data cannot be added.
        $duplicate_id = $queue->createItem($data);
        self::assertFalse($duplicate_id);
        // The ordering should have been preserved.
        $item = $queue->claimItem();
        self::assertEquals($data, $item->data);
      }
    }
  }

  /**
   * Adds a queue item and stores it directly to the queue.
   *
   * This code mimics the code that was in UniqueDatabaseQueue::doCreateItem()
   * before the update to sha2.
   *
   * @param string $name
   *   The queue name.
   * @param mixed $data
   *   Arbitrary data to be associated with the new task in the queue.
   *
   * @return string|false
   *   A unique ID if the item was successfully created. False otherwise.
   */
  protected function createItemMd5($name, $data) {
    $connection = $this->container->get('database');
    try {
      $query = $connection->insert(UniqueDatabaseQueue::TABLE_NAME)
        ->fields([
          'name' => $name,
          'data' => serialize($data),
          'created' => time(),
          // Generate a unique value for this data on this queue. This value
          // is ignored by the update hook, so we can use any hash.
          'md5' => substr(hash('sha512', $name . serialize($data)), 0, 32),
        ]);
      return $query->execute();
    }
    catch (IntegrityConstraintViolationException $e) {
      return FALSE;
    }
  }

  /**
   * Schema definition before update 8001.
   *
   * @return array
   *   DB table schema.
   */
  protected function oldSchemaDefinition() {
    $db_queue = $this->container->get('queue.database')->get('dummy');
    return array_merge_recursive(
      $db_queue->schemaDefinition(),
      // This is the previous schema that was in
      // \Drupal\queue_unique\UniqueDatabaseQueue::schemaDefinition().
      [
        'fields' => [
          'md5' => [
            'type' => 'char',
            'length' => 32,
            'not null' => TRUE,
          ],
        ],
        'unique keys' => [
          'unique' => ['md5'],
        ],
      ]
    );
  }

}
