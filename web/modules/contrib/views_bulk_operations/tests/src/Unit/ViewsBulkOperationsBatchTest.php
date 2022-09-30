<?php

namespace Drupal\Tests\views_bulk_operations\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\ViewsBulkOperationsBatch
 * @group views_bulk_operations
 */
class ViewsBulkOperationsBatchTest extends UnitTestCase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static array $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * Returns a stub ViewsBulkOperationsActionProcessor that returns dummy data.
   *
   * @return \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor
   *   A mocked action processor.
   */
  public function getViewsBulkOperationsActionProcessorStub($entities_count): ViewsBulkOperationsActionProcessor {
    $actionProcessor = $this->getMockBuilder('Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor')
      ->disableOriginalConstructor()
      ->getMock();

    $actionProcessor->expects($this->any())
      ->method('populateQueue')
      ->will($this->returnValue($entities_count));

    $actionProcessor->expects($this->any())
      ->method('process')
      ->will($this->returnCallback(static function () use ($entities_count) {
        $return = [];
        for ($i = 0; $i < $entities_count; $i++) {
          $return[] = [
            'message' => 'Some action',
          ];
        }
        return $return;
      }));

    return $actionProcessor;
  }

  /**
   * Tests the getBatch() method.
   *
   * @covers ::getBatch
   */
  public function testGetBatch(): void {
    $data = [
      'list' => [[0, 'en', 'node', 1]],
      'some_data' => [],
      'action_label' => '',
      'finished_callback' => [TestViewsBulkOperationsBatch::class, 'finished'],
    ];
    $batch = TestViewsBulkOperationsBatch::getBatch($data);
    $this->assertArrayHasKey('title', $batch);
    $this->assertArrayHasKey('operations', $batch);
    $this->assertArrayHasKey('finished', $batch);
  }

  /**
   * Tests the finished() method.
   *
   * @covers ::finished
   */
  public function testFinished(): void {
    $results = [
      'operations' => [
        [
          'message' => 'Some operation',
          'type' => 'status',
          'count' => 2,
        ],
      ],
      'api_version' => '1',
    ];
    TestViewsBulkOperationsBatch::finished(TRUE, $results, []);
    $this->assertEquals('Action processing results: Some operation (2).', TestViewsBulkOperationsBatch::message());

    $results = [
      'operations' => [
        [
          'message' => 'Some operation1',
          'type' => 'status',
          'count' => 1,
        ],
        [
          'message' => 'Some operation2',
          'type' => 'status',
          'count' => 1,
        ],
      ],
      'api_version' => '1',
    ];

    TestViewsBulkOperationsBatch::finished(TRUE, $results, []);
    $this->assertEquals('Action processing results: Some operation1 (1). | Action processing results: Some operation2 (1).', TestViewsBulkOperationsBatch::message());
  }

}
