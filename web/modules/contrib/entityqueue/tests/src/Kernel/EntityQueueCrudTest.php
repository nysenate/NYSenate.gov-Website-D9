<?php

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests creation, loading, updating, deleting of entity queue.
 *
 * @group entityqueue
 */
class EntityQueueCrudTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entityqueue', 'system', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setup() {
    parent::setUp();
    $this->installEntitySchema('entity_subqueue');
  }

  /**
   * Tests CRUD operations for entity queue.
   */
  public function testEntityQueueCrud() {
    $entity_queue = NULL;
    // Add a entity queue with minimum data only.
    foreach (['simple', 'multiple'] as $handler) {
      foreach (['node', 'user'] as $target_type) {
        $entity_queue = EntityQueue::create([
          'id' => $this->randomMachineName(),
          'label' => $this->randomString(),
          'handler' => $handler,
          'entity_settings' => [
            'target_type' => $target_type,
          ],
        ]);
        $entity_queue->save();
        $this->assetEntityQueue($entity_queue);
      }
    }

    $id = $entity_queue->id();
    // Delete queue.
    $entity_queue->delete();
    $this->assertNull(EntityQueue::load($id));
  }

  /**
   * Verifies that entity queue is properly stored.
   *
   * @param \Drupal\entityqueue\Entity\EntityQueue $expected
   *   Entity Queue.
   */
  public function assetEntityQueue(EntityQueue $expected) {
    $actual = EntityQueue::load($expected->id());
    $this->assertEquals($expected->id(), $actual->id());
    $this->assertEquals($expected->getTargetEntityTypeId(), $actual->getTargetEntityTypeId());
    $this->assertEquals($expected->getHandler(), $actual->getHandler());
    $this->assertEquals($expected->getEntitySettings(), $actual->getEntitySettings());
    $this->assertEquals($expected->getQueueSettings(), $actual->getQueueSettings());
    $this->assertEquals($expected->getMinimumSize(), $actual->getMinimumSize());
    $this->assertEquals($expected->getMaximumSize(), $actual->getMaximumSize());
    $this->assertEquals($expected->getActAsQueue(), $actual->getActAsQueue());
    $this->assertEquals($expected->isReversed(), $actual->isReversed());
    $this->assertEquals($expected->getHandlerPlugin(), $actual->getHandlerPlugin());
    $this->assertEquals($expected->getPluginCollections(), $actual->getPluginCollections());
  }

}
