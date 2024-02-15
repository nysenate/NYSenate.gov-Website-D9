<?php

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests entity subqueue API.
 *
 * @group entityqueue
 */
class EntitySubQueueAPITest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entityqueue'];

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('entity_subqueue');
  }

  /**
   * Tests the entity subqueue API.
   */
  public function testSubqueueAPI() {
    $id = $this->randomMachineName();
    $queue = EntityQueue::create([
      'id' => $id,
      'label' => $this->randomString(),
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $queue->save();
    $subqueue = EntitySubqueue::load($queue->id());
    $entity = EntityTest::create();
    $entity->save();

    $subqueue->addItem($entity)->save();
    $this->assertTrue($subqueue->hasItem($entity));
    $entity->delete();
    $subqueue = EntitySubqueue::load($queue->id());
    $this->assertFalse($subqueue->hasItem($entity));
  }

}
