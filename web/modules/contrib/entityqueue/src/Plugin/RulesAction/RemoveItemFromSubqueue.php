<?php

namespace Drupal\entityqueue\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Remove item from subqueue' action.
 *
 * @RulesAction(
 *   id = "entityqueue_remove_item",
 *   label = @Translation("Remove item from a subqueue"),
 *   category = @Translation("Entityqueue"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be removed from the subqueue.")
 *     ),
 *     "subqueue" = @ContextDefinition("string",
 *       label = @Translation("Subqueue"),
 *       description = @Translation("Specifies the ID of the subqueue where the item will be removed.")
 *     )
 *   }
 * )
 */
class RemoveItemFromSubqueue extends RulesActionBase {

  /**
   * Removes the given entity from a subqueue.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be removed from the subqueue.
   * @param string $subqueue_id
   *   The ID of the subqueue where the entity will be remove.
   */
  protected function doExecute(EntityInterface $entity, $subqueue_id) {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = EntitySubqueue::load($subqueue_id);
    $subqueue->removeItem($entity)->save();
  }

}
