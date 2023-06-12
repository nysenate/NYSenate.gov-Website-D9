<?php

namespace Drupal\Tests\rh_node\Functional;

use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorInvocationTestBase;

/**
 * Test that rabbit hole behaviors are invoked correctly for nodes.
 *
 * @group rh_node
 */
class NodeBehaviorInvocationTest extends RabbitHoleBehaviorInvocationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'node';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_node', 'node'];

  const TEST_BUNDLE = 'rh_node_test_content_type';

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle($action = NULL) {
    $bundle = $this->drupalCreateContentType([
      'type' => self::TEST_BUNDLE,
    ]);
    if (isset($action)) {
      $this->behaviorSettingsManager->saveBehaviorSettings([
        'action' => $action,
        'allow_override' => TRUE,
      ], 'node_type', $bundle->id());
    }
    return $bundle->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [
      'type' => self::TEST_BUNDLE,
    ];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateNode($values);
  }

}
