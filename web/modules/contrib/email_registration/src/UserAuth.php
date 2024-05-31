<?php

declare(strict_types=1);

namespace Drupal\email_registration;

use Drupal\user\UserAuth as CoreUserAuth;

/**
 * Extends the core authentication to check email or username, and password.
 *
 * Core authentication only checks username and password, but not email, so
 * we need to adapt the query to cater for both.
 */
class UserAuth extends CoreUserAuth {

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
    $uid = FALSE;

    if (!empty($username) && strlen($password) > 0) {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('user');

      $query = $storage->getQuery();
      $query->accessCheck(TRUE);

      $or = $query->orConditionGroup();
      $or->condition('name', $username);
      $or->condition('mail', $username);

      $query->condition($or);

      $result = $query->execute();

      $account_search = $storage->loadMultiple($result);
      if ($account = reset($account_search)) {
        if ($this->passwordChecker->check($password, $account->getPassword())) {
          // Successful authentication.
          $uid = $account->id();

          // Update user to new password scheme if needed.
          if ($this->passwordChecker->needsRehash($account->getPassword())) {
            $account->setPassword($password);
            $account->save();
          }
        }
      }
    }

    return $uid;
  }

}
