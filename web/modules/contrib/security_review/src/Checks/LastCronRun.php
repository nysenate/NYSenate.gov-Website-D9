<?php

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks the last time cron has ran.
 */
class LastCronRun extends Check {

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
    return 'Last cron run';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'last_cron_run';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $last_run = TRUE;
    $cron_last = \Drupal::state()->get('system.cron_last');
    if ($cron_last <= strtotime('-3 day')) {
      $result = CheckResult::FAIL;
      $last_run = FALSE;
    }

    return $this->createResult($result, ['last_run' => $last_run]);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('A properly configured cron job executes, initiates, or manages a variety of tasks.');
    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Cron has ran in last 3 days.'),
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

    return $this->t('Last cron run');
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Cron has ran within the last 3 days.');

      case CheckResult::FAIL:
        return $this->t('Cron has not ran within the last 3 days.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
