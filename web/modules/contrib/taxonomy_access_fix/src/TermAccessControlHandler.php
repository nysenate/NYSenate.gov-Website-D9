<?php

namespace Drupal\taxonomy_access_fix;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermAccessControlHandler as OriginalTermAccessControlHandler;

/**
 * Extends Core's access control handler with a view permission by bundle.
 */
class TermAccessControlHandler extends OriginalTermAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    if ($operation !== 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    $access_result = AccessResult::allowedIfHasPermission($account, "view terms in {$entity->bundle()}")
      ->andIf(AccessResult::allowedIf($entity->isPublished()))
      ->cachePerPermissions()
      ->addCacheableDependency($entity);
    if (!$access_result->isAllowed()) {
      /** @var \Drupal\Core\Access\AccessResultReasonInterface $access_result */
      $access_result->setReason("The 'view terms in {$entity->bundle()}' OR 'administer taxonomy' permission is required and the taxonomy term must be published.");
    }
    return $access_result;
  }

}
