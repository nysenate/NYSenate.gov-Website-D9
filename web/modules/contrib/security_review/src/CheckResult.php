<?php

namespace Drupal\security_review;

/**
 * Used to define the result of a Check.
 */
class CheckResult {

  const SUCCESS = 0;
  const FAIL = 1;
  const WARN = 2;
  const INFO = 3;

  /**
   * Stores the parent Check.
   *
   * @var \Drupal\security_review\Check
   */
  private $check;

  /**
   * Stores the outcome of the check.
   *
   * @var int
   */
  private $result;

  /**
   * Stores findings.
   *
   * @var array
   */
  private $findings;

  /**
   * Stores the timestamp of the check run.
   *
   * @var int
   */
  private $time;

  /**
   * Whether the result should be shown on the UI.
   *
   * @var bool
   */
  private $visible;

  /**
   * Stores hushed findings.
   *
   * @var array
   */
  private $hushedFindings;

  /**
   * Constructs an immutable CheckResult.
   *
   * @param \Drupal\security_review\Check $check
   *   The Check that created this result.
   * @param int $result
   *   The result integer (see the constants defined above).
   * @param array $findings
   *   The findings.
   * @param bool $visible
   *   The visibility of the result.
   * @param int $time
   *   The timestamp of the check run.
   * @param array $hushedFindings
   *   The hushed findings.
   */
  public function __construct(Check $check, $result, array $findings, bool $visible = TRUE, int $time = NULL, array $hushedFindings = []) {
    // Set the parent check.
    $this->check = $check;

    // Set the result value.
    $result = intval($result);
    if ($result < self::SUCCESS || $result > self::INFO) {
      $result = self::INFO;
    }
    $this->result = $result;

    // Set the findings.
    $this->findings = $findings;

    // Set the hushed findings.
    $this->hushedFindings = $hushedFindings;

    // Set the visibility.
    $this->visible = $visible;

    // Set the timestamp.
    if (!is_int($time)) {
      $this->time = time();
    }
    else {
      $this->time = $time;
    }
  }

  /**
   * Combines two CheckResults.
   *
   * Combines two CheckResults and returns a new one with the old one's fields
   * except for the findings which are copied from the fresh result.
   *
   * @param \Drupal\security_review\CheckResult $old
   *   The old result to clone.
   * @param \Drupal\security_review\CheckResult $fresh
   *   The new result to copy the findings from.
   *
   * @return \Drupal\security_review\CheckResult
   *   The combined result.
   */
  public static function combine(CheckResult $old, CheckResult $fresh) {
    return new CheckResult($old->check, $old->result, $fresh->findings, $old->visible, $old->time, $fresh->hushedFindings);
  }

  /**
   * Returns the parent Check.
   *
   * @return \Drupal\security_review\Check
   *   The Check that created this result.
   */
  public function check() {
    return $this->check;
  }

  /**
   * Returns the outcome of the check.
   *
   * @return int
   *   The result integer.
   */
  public function result() {
    return $this->result;
  }

  /**
   * Returns the findings.
   *
   * @return array
   *   The findings. Contents of this depends on the actual check.
   */
  public function findings(): array {
    return $this->findings;
  }

  /**
   * Returns the hushed findings.
   *
   * @return array
   *   The hushed findings. Contents of this depends on the actual check.
   */
  public function hushedFindings(): array {
    return $this->hushedFindings;
  }

  /**
   * Returns the timestamp.
   *
   * @return int
   *   The timestamp the result was created on.
   */
  public function time() {
    return $this->time;
  }

  /**
   * Returns the visibility of the result.
   *
   * @return bool
   *   Whether the result should be shown on the UI.
   */
  public function isVisible() {
    return $this->visible;
  }

  /**
   * Returns the result message.
   *
   * @return string
   *   The result message for this result.
   */
  public function resultMessage() {
    return $this->check->getMessage($this->result);
  }

}
