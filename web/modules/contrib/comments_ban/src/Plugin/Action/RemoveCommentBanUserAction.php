<?php

namespace Drupal\comments_ban\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Action\ActionBase;

/**
 * Action to remove a comment and ban the comments author.
 *
 * @Action(
 *   id = "ban_user_comment",
 *   label = @Translation("Remove comment and ban user"),
 *   type = "comment"
 * )
 */
class RemoveCommentBanUserAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      $user = $entity->uid->entity;
      $user->field_user_banned_comments = 1;
      $user->save();
      $entity->delete();
    }
    return $this->t('Removed comment and user banned');
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
