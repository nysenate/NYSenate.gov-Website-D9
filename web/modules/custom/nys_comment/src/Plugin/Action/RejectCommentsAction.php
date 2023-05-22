<?php

namespace Drupal\nys_comment\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Action\ActionBase;

/**
 * Action to ban the comments author.
 *
 * @Action(
 *   id = "reject_comment",
 *   label = @Translation("Rejects selected comments."),
 *   type = "comment"
 * )
 */
class RejectCommentsAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      $entity->field_rejected = TRUE;
      $entity->save();
    }
    return $this->t('Rejected comment');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($account->hasPermission('administer comments')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
