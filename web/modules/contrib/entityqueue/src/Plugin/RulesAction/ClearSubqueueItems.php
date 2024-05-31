<?php

namespace Drupal\entityqueue\Plugin\RulesAction;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Clear subqueue items' action.
 *
 * @RulesAction(
 *   id = "entityqueue_clear_items",
 *   label = @Translation("Clear subqueue items"),
 *   category = @Translation("Entityqueue"),
 *   context_definitions = {
 *     "subqueue" = @ContextDefinition("string",
 *       label = @Translation("Subqueue"),
 *       description = @Translation("Specifies the ID of the subqueue whose items will be cleared.")
 *     )
 *   }
 * )
 */
class ClearSubqueueItems extends RulesActionBase {

  /**
   * Clears the items of a  subqueue.
   *
   * @param string $subqueue_id
   *   The ID of the subqueue whose items will be cleared.
   */
  protected function doExecute($subqueue_id) {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = EntitySubqueue::load($subqueue_id);
    $subqueue->clearItems()->save();
  }

}
