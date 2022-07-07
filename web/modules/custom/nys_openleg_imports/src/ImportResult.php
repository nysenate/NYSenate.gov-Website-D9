<?php

namespace Drupal\nys_openleg_imports;

/**
 * Provides for reporting import result statistics.
 */
class ImportResult {

  /**
   * Count of successful imports.
   *
   * @var int
   */
  protected int $success = 0;

  /**
   * Count of failed imports.
   *
   * @var int
   */
  protected int $fail = 0;

  /**
   * Count of skipped imports.
   *
   * @var int
   */
  protected int $skipped = 0;

  /**
   * List of exception messages generated during the processing.
   *
   * @var array
   */
  protected array $exceptions = [];

  /**
   * Adds an exception message.
   */
  public function addException(string $message) {
    $this->exceptions[] = $message;
  }

  /**
   * Adds one to success.
   */
  public function addSuccess() {
    $this->modifyValue('success', 1);
  }

  /**
   * Adds one to fails.
   */
  public function addFail() {
    $this->modifyValue('fail', 1);
  }

  /**
   * Adds one to skipped.
   */
  public function addSkip() {
    $this->modifyValue('skipped', 1);
  }

  /**
   * Modifies one of the values through simple addition (allows negatives).
   *
   * @param string $type
   *   Which value to modify, though no name checking is done.
   * @param int $num
   *   The amount to modify the value.  Can be negative.
   */
  public function modifyValue(string $type, int $num) {
    $this->$type += $num;
  }

  /**
   * Getter for Success.
   */
  public function getSuccess(): int {
    return $this->success;
  }

  /**
   * Getter for Fail.
   */
  public function getFail(): int {
    return $this->fail;
  }

  /**
   * Getter for Fail.
   */
  public function getSkipped(): int {
    return $this->skipped;
  }

  /**
   * Gets the total processed.
   */
  public function total(): int {
    return $this->success + $this->fail + $this->skipped;
  }

  /**
   * Getter for Exceptions.
   */
  public function getExceptions(): array {
    return $this->exceptions;
  }

}
