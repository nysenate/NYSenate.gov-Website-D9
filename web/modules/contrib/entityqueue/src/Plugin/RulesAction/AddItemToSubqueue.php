<?php

namespace Drupal\entityqueue\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Add item to subqueue' action.
 *
 * @RulesAction(
 *   id = "entityqueue_add_item",
 *   label = @Translation("Add item to a subqueue"),
 *   category = @Translation("Entityqueue"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be added to the subqueue.")
 *     ),
 *     "subqueue" = @ContextDefinition("string",
 *       label = @Translation("Subqueue"),
 *       description = @Translation("Specifies the ID of the subqueue where the new item will be added.")
 *     )
 *   }
 * )
 */
class AddItemToSubqueue extends RulesActionBase {

  /**
   * Adds the given entity to a subqueue.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be added to the subqueue.
   * @param string $subqueue_id
   *   The ID of the subqueue where the entity will be added.
   */
  protected function doExecute(EntityInterface $entity, $subqueue_id) {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = EntitySubqueue::load($subqueue_id);
    $subqueue->addItem($entity)->save();
  }

}
