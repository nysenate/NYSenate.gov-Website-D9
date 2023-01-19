<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Node is scheduled for publishing' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_node_scheduled_for_publishing",
 *   label = @Translation("Node is scheduled for publishing"),
 *   category = @Translation("Scheduler"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Scheduled Node"),
 *       description = @Translation("The node to test for having a scheduled publishing date. Enter 'node' or use data selection.")
 *     )
 *   }
 * )
 */
class NodeIsScheduledForPublishing extends RulesConditionBase {

  /**
   * Determines whether a node is scheduled for publishing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node to be checked.
   *
   * @return bool
   *   TRUE if the node is scheduled for publishing, FALSE if not.
   */
  protected function doEvaluate(EntityInterface $node) {
    return !empty($node->publish_on->value);
  }

}
