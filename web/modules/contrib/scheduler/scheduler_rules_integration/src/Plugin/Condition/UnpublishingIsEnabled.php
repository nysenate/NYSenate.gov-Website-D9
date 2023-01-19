<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Unpublishing is enabled' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_unpublishing_is_enabled",
 *   label = @Translation("Node type is enabled for scheduled unpublishing"),
 *   category = @Translation("Scheduler"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Scheduled Node"),
 *       description = @Translation("The node to check for scheduled unpublishing enabled. Enter 'node' or use data selection.")
 *     )
 *   }
 * )
 */
class UnpublishingIsEnabled extends RulesConditionBase {

  /**
   * Determines whether scheduled unpublishing is enabled for this node type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node to be checked.
   *
   * @return bool
   *   TRUE if scheduled unpublishing is enabled for the content type of this
   *   node.
   */
  protected function doEvaluate(EntityInterface $node) {
    $config = \Drupal::config('scheduler.settings');
    return ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $config->get('default_unpublish_enable')));
  }

}
