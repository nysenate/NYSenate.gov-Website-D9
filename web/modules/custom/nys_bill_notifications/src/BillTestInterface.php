<?php

namespace Drupal\nys_bill_notifications;

/**
 * Interface for BillTest plugins.
 */
interface BillTestInterface {

  /**
   * Gets the pattern used by this test.
   */
  public function getPattern(): array;

  /**
   * Indicates if the passed update block matches this test's pattern.
   */
  public function isMatch(object $update): bool;

  /**
   * Getter for priority.
   */
  public function getPriority(): int;

  /**
   * Setter for priority.
   */
  public function setPriority(int $priority): self;

  /**
   * Getter for plugin id.
   */
  public function getId(): string;

  /**
   * Getter for test name.
   */
  public function getName(): string;

  /**
   * Getter for the formatted description of a successful match.
   */
  public function getSummary(object $update): string;

  /**
   * Indicates if the test is disabled.
   */
  public function isDisabled(): bool;

  /**
   * Indicates if the test is enabled.
   */
  public function isEnabled(): bool;

  /**
   * Generates context data points for an update.
   *
   * Some updates require some bit of information from a successful test, e.g.,
   * substitutions need the print number of the other bill.
   *
   * @param object $update
   *   An OpenLeg update block.
   *
   * @return array
   *   An array of data points pulled from the update.
   */
  public function context(object $update): array;

}
