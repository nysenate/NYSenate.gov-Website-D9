<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks for abundant query errors.
 */
class QueryErrors extends Check {

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
    return 'Query errors';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If dblog is not enabled return with hidden INFO.
    if (!$this->moduleHandler()->moduleExists('dblog')) {
      return $this->createResult(CheckResult::INFO, [], FALSE);
    }

    $result = CheckResult::SUCCESS;
    $findings = [];
    $last_result = $this->lastResult();
    $visible = FALSE;

    // Prepare the query.
    $query = $this->database()->select('watchdog', 'w');
    $query->fields('w', [
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'hostname',
    ]);
    $query->condition('type', 'php')->condition('severity', RfcLogLevel::ERROR);
    if ($last_result instanceof CheckResult) {
      // Only check entries that got recorded since the last run of the check.
      $query->condition('timestamp', $last_result->time(), '>=');
    }

    // Execute the query.
    $db_result = $query->execute();

    // Count the number of query errors per IP.
    $entries = [];
    foreach ($db_result as $row) {
      // Get the message.
      if ($row->variables === 'N;') {
        $message = $row->message;
      }
      else {
        $message = $this->t($row->message, unserialize($row->variables));
      }

      // Get the IP.
      $ip = $row->hostname;

      // Search for query errors.
      $message_contains_sql = strpos($message, 'SQL') !== FALSE;
      $message_contains_select = strpos($message, 'SELECT') !== FALSE;
      if ($message_contains_sql && $message_contains_select) {
        $entry_for_ip = &$entries[$ip];

        if (!isset($entry_for_ip)) {
          $entry_for_ip = 0;
        }
        $entry_for_ip++;
      }
    }

    // Filter the IPs with more than 10 query errors.
    if (!empty($entries)) {
      foreach ($entries as $ip => $count) {
        if ($count > 10) {
          $findings[] = $ip;
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
      $visible = TRUE;
    }

    return $this->createResult($result, $findings, $visible);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('Database errors triggered from the same IP may be an artifact of a malicious user attempting to probe the system for weaknesses like SQL injection or information disclosure.');

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Abundant query errors from the same IP'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return [];
    }

    $paragraphs = [];
    $paragraphs[] = $this->t('The following IPs were observed with an abundance of query errors.');

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $result->findings(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return '';
    }

    $output = $this->t('Suspicious IP addresses:') . ":\n";
    foreach ($findings as $ip) {
      $output .= "\t" . $ip . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::FAIL:
        return $this->t('Query errors from the same IP. These may be a SQL injection attack or an attempt at information disclosure.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
