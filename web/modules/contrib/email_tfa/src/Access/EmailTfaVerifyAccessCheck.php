<?php

namespace Drupal\email_tfa\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Define Email TFA form access.
 */
class EmailTfaVerifyAccessCheck implements AccessInterface {

  /**
   * Checks access to access Email TFA form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $temp_store = \Drupal::service('tempstore.private')->get('email_tfa');
    $config = \Drupal::config('email_tfa.settings');
    $status = $config->get('status');
    $ignore_role = $config->get('ignore_role');
    $tracks = $config->get('tracks');
    // Check if module is active from its settings and if .
    if ($status == 0 && $tracks == 'globally_enabled' && _email_tfa_in_array_any($account->getRoles(), $ignore_role)) {
      return AccessResult::forbidden();
    }

    if ($temp_store->get('email_tfa_user_verify') == 0 && $account->isAuthenticated()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();

  }

}
