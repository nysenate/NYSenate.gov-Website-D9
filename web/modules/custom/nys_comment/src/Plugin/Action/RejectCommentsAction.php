<?php

namespace Drupal\nys_comment\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
  public function execute($entity = NULL): TranslatableMarkup {
    if ($entity) {
      $entity->field_rejected = TRUE;
      $entity->save();
    }
    return $this->t('Rejected comment');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface {
    if ($account->hasPermission('administer comments')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
