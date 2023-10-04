<?php

namespace Drupal\nys_bill_notifications;

use Drupal\nys_bill_notifications\Exception\InvalidUpdateBlockException;

/**
 * Describes a collection of test results against an OpenLeg bill update block.
 */
class MatchResults {

  /**
   * Array of matching events for this update block.
   *
   * @var array
   */
  protected array $matches;

  /**
   * The OpenLeg update block.
   *
   * @var object
   */
  protected object $update;

  /**
   * Constructor.
   *
   * @param object $update
   *   A JSON-decoded OpenLeg bill update block.
   *
   * @throws \Drupal\nys_bill_notifications\Exception\InvalidUpdateBlockException
   *   If bill session or print number can not be determined.
   */
  public function __construct(object $update) {
    $session = $update->id->session ?? '';
    $print = $update->id->basePrintNo ?? '';

    // If session or print number are not found, stop here.
    if (!($session && $print)) {
      throw new InvalidUpdateBlockException('Could not resolve bill ID from update block');
    }

    // Initialize the update block and test collection.
    $this->update = $update;
  }

  /**
   * Getter for bill's session year.
   */
  public function getSession(): string {
    return $this->update->id->session;
  }

  /**
   * Getter for bill's base print number.
   */
  public function getBasePrint(): string {
    return $this->update->id->basePrintNo;
  }

  /**
   * Gets the full print number for a bill.
   */
  public function getFullPrint(): string {
    return $this->getSession() . '-' . $this->getBasePrint();
  }

  /**
   * Getter for the update's processing timestamp.
   *
   * This indicates the time at which this update was last processed
   * by OpenLeg.  It can change over time.
   *
   * @see \Drupal\nys_openleg_api\Request::OPENLEG_TIME_FULL
   */
  public function getProcessed(): string {
    return $this->update->processedDateTime;
  }

  /**
   * Getter for the update's source timestamp.
   *
   * This indicates the time at which the update was officially published
   * by LBDC.  It should remain constant over time.
   *
   * @see \Drupal\nys_openleg_api\Request::OPENLEG_TIME_FULL
   */
  public function getSourceTime(): string {
    return $this->update->sourceDateTime;
  }

  /**
   * Adds a matching event to the results.
   */
  public function addMatch(BillTestInterface $test) {
    $this->matches[$test->getId()] = $test;
  }

  /**
   * Removes a test, based on ID.
   */
  public function removeMatch(string $id) {
    unset($this->matches[$id]);
  }

  /**
   * Gets the matched tests.
   */
  public function getMatches(): array {
    return $this->matches;
  }

  /**
   * Gets an array of all test IDs.
   */
  public function getMatchIds(): array {
    return array_keys($this->matches);
  }

  /**
   * Gets the count of matches.
   */
  public function getMatchCount(): int {
    return isset($this->matches) ? count($this->matches) : 0;
  }

  /**
   * Gets the update which was tested.
   */
  public function getUpdate(): object {
    return $this->update;
  }

}
