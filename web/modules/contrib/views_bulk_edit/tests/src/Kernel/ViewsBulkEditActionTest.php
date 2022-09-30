<?php

namespace Drupal\Tests\views_bulk_edit\Kernel;

use Drupal\Tests\views_bulk_operations\Kernel\ViewsBulkOperationsKernelTestBase;
use Drupal\node\NodeInterface;

/**
 * @coversDefaultClass \Drupal\views_bulk_edit\Plugin\Action\ModifyEntityValues
 * @group views_bulk_edit
 */
class ViewsBulkEditActionTest extends ViewsBulkOperationsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views_bulk_edit',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->createTestNodes([
      'page' => [
        'count' => 10,
      ],
    ]);
  }

  /**
   * Tests the bulk edit action.
   *
   * @covers ::getViewBundles
   * @covers ::execute
   */
  public function testModifyEntityValues() {
    $vbo_data = [
      'view_id' => 'views_bulk_operations_test',
      'action_id' => 'views_bulk_edit',
      'configuration' => [
        'node' => [
          'page' => [
            'values' => [
              'status' => [
                ['value' => 0],
              ],
            ],
            'change_method' => [
              'status' => 'replace',
            ],
          ],
        ],
      ],
    ];

    // Get list of rows to process from different view pages.
    $selection = [0, 3, 6, 8];
    $vbo_data['list'] = $this->getResultsList($vbo_data, $selection);

    // Execute the action.
    $results = $this->executeAction($vbo_data);

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    $statuses = [];

    foreach ($this->testNodesData as $id => $lang_data) {
      $node = $nodeStorage->load($id);
      $status = intval($node->status->value);
      foreach ($vbo_data['list'] as $item) {
        if ($item[3] == $id) {
          $this->assertEquals(NodeInterface::NOT_PUBLISHED, $status);
          break 2;
        }
      }
      $this->assertEquals(NodeInterface::PUBLISHED, $status);
    }
  }

}
