<?php

namespace Drupal\security_review\Checks;

use Drupal\Component\Utility\Html;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\user\Entity\User;

/**
 * Checks if a user has the same name and password.
 *
 * @package Drupal\security_review\Checks
 */
class NamePasswords extends Check {

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
    return $this->t('Username same as password');
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $findings = [];
    $result = CheckResult::FAIL;

    // Fetch all users and test if their password is the same as their username.
    $user_ids = \Drupal::entityQuery('user')
      ->accessCheck()
      ->condition('uid', 0, '<>')
      ->execute();
    $users = User::loadMultiple($user_ids);

    /** @var \Drupal\user\UserAuth $user_auth */
    $user_auth = \Drupal::service('user.auth');

    /** @var \Drupal\user\Entity\User $user */
    foreach ($users as $user) {
      if ($user_auth->authenticate($user->getDisplayName(), $user->getDisplayName())) {
        $findings[] = $user->getDisplayName();
      }
    }

    if (empty($findings)) {
      $result = CheckResult::SUCCESS;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('Verifies that users have not set their password to be the same as their username.');

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Username same as password'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    if ($result->result() !== CheckResult::FAIL) {
      return [];
    }

    $users = $result->findings();
    $user_list = [];
    foreach ($users as $user) {
      $user_list[] = Html::escape($user);
    }

    $paragraphs[] = $this->t('The following user(s) has their password set to be the same as their username');
    $paragraphs[] = [
      '#theme' => 'item_list',
      '#items' => $user_list,
    ];
    $paragraphs[] = $this->t('Consider installing the <a href="https://www.drupal.org/project/password_policy">Password Policy</a> module, to enforce users to have a stronger password.');

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('No users, with matching username and password, found.');

      case CheckResult::FAIL:
        return $this->t('Users, with matching username and password, found.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
