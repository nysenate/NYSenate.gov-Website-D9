<?php

namespace Drupal\Tests\yaml_content\Unit\Plugin\yaml_content\process;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\yaml_content\Traits\LoadFixturesTrait;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\yaml_content\process\Reference;
use Prophecy\Argument;

/**
 * Test entity reference processing.
 *
 * @group yaml_content
 *
 * @coversDefaultClass \Drupal\yaml_content\Plugin\yaml_content\process\Reference
 */
class ReferenceTest extends UnitTestCase {

  /**
   * The entity type manager service mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The entity storage handler mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorageHandler;

  /**
   * The reference process plugin being tested.
   *
   * @var \Drupal\yaml_content\Plugin\yaml_content\process\Reference
   */
  protected $reference;

  /**
   * Setup mocks and a reference plugin for all tests.
   */
  public function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityStorageHandler = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager->getStorage(Argument::type('string'))
      ->willReturn($this->entityStorageHandler->reveal());
    $this->reference = new Reference([
      'my_entity',
      [
        'title' => 'My First Blog Post',
      ],
    ], 'reference', [], $this->entityTypeManager->reveal());
  }

  /**
   * Reference processing returns a reference to existing matching entities.
   *
   * @covers ::process
   */
  public function testProcessExisting() {
    $data = ['#process' => ['callback' => 'reference']];

    // Mock a query so we can control and assert the mapping of arguments to entity.
    $query = $this->prophesize(QueryInterface::class);
    $query->condition('title', 'My First Blog Post')
      ->shouldBeCalled();
    $query->execute()
      ->shouldBeCalled()
      ->willReturn([1]);
    $this->entityStorageHandler->getQuery('AND')
      ->shouldBeCalled()
      ->willReturn($query->reveal());

    // Create should not be called.
    $this->entityStorageHandler->create(Argument::any())
      ->shouldNotBeCalled();

    $this->reference->process(new ProcessingContext(), $data);
    $this->assertArrayEquals(['target_id' => 1], $data);
  }

  /**
   * Reference processing should not create new entity if no matches are found.
   *
   * @covers ::process
   */
  public function testProcessCreate() {
    $data = ['#process' => ['callback' => 'reference']];

    // Mock a query so we can control and assert the mapping of arguments to entity.
    $query = $this->prophesize(QueryInterface::class);
    $query->condition('title', 'My First Blog Post')
      ->shouldBeCalled();
    $query->execute()
      ->shouldBeCalled()
      ->willReturn();
    $this->entityStorageHandler->getQuery('AND')
      ->shouldBeCalled()
      ->willReturn($query->reveal());

    // Stub an existing entity since entities are complex and db bound.
    $entity = $this->prophesize(EntityInterface::class);
    $entity->id()->willReturn(2);

    // Create should not be called.
    $this->entityStorageHandler->create(Argument::any())
      ->shouldBeCalled()
      ->willReturn($entity);

    $this->reference->process(new ProcessingContext(), $data);
    $this->assertArrayEquals(['target_id' => 2], $data);
  }

  /**
   * Test reference processing failure.
   */
  public function testProcessCreateFail() {
    $data = ['#process' => ['callback' => 'reference']];

    // Mock a query so we can control and assert the mapping of arguments to entity.
    $query = $this->prophesize(QueryInterface::class);
    $query->condition('title', 'My First Blog Post')
      ->shouldBeCalled();
    $query->execute()
      ->shouldBeCalled()
      ->willReturn();
    $this->entityStorageHandler->getQuery('AND')
      ->shouldBeCalled()
      ->willReturn($query->reveal());

    // Stub an existing entity since entities are complex and db bound.
    $entity = $this->prophesize(EntityInterface::class);
    $entity->id()->willReturn(NULL);
    $this->markTestIncomplete('How do we fail? Id will always be called and the array will always have something and not be empty.');

    // Create should not be called.
    $this->entityStorageHandler->create(Argument::any())
      ->shouldBeCalled()
      ->willReturn($entity);

    $this->reference->process(new ProcessingContext(), $data);
    $this->assertArrayEquals(['target_id' => 2], $data);
  }

}
