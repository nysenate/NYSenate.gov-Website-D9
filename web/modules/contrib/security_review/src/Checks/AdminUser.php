<?php

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\user\Entity\User;

/**
 * Checks whether admin account is blocked.
 */
class AdminUser extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Blocked Admin account';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'blocked_admin_account';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::FAIL;
    $blocked = FALSE;
    $admin = User::load(1);
    if ($admin->isBlocked()) {
      $result = CheckResult::SUCCESS;
      $blocked = TRUE;
    }

    return $this->createResult($result, ['admin' => $blocked]);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('The administrative account (uid 1) is commonly targeted by attackers because this account has superuser privileges which cannot be blocked or limited.  Attacks that do things like change the administrator password, or even brute force or social engineering attacks could compromise the administrator password.  Because the administrative account has such wide privileges it is a good idea to create a role for administrators and explicitly create these less privileged accounts.  The administrative account can be unblocked by users with the "administer users" permission if you need to use the account at a later time.');
    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Administrative account disabled'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    if ($result->result() != CheckResult::FAIL) {
      return '';
    }

    return $this->t('Admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('The administrative account is disabled (protected).');

      case CheckResult::FAIL:
        return $this->t('The administrative account is enabled (dangerous!).');

      default:
        return $this->t("Unexpected result.");
    }
  }

}
