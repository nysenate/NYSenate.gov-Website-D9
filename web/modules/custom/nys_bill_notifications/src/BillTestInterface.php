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

}
