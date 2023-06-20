<?php

namespace Drupal\nys_comment\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Action unban users from the comments system.
 *
 * @Action(
 *   id = "unban_user_comment",
 *   label = @Translation("Unban users from the comments"),
 *   type = "user"
 * )
 */
class UnbanUserCommentsAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity) {
      $entity->field_user_banned_comments = 0;
      $entity->save();
    }
    return $this->t('User allowed to use the comments system');
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
