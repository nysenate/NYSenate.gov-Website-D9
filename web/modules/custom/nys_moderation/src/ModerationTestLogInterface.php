<?php

namespace Drupal\nys_moderation;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Moderation Test Log entities.
 */
interface ModerationTestLogInterface extends ContentEntityInterface {

  /**
   * Get pass/fail stats for this log thread.
   *
   * The return array is expected to be in the form:
   *   [
   *     'actual' => ['pass' => (int), 'fail' => (int)],
   *     'expected' => ['pass' => (int), 'fail' => (int)]
   *   ]
   */
  public function getPassFail(): array;

  /**
   * Provides a count of the associated log items.
   */
  public function itemCount(): int;

  /**
   * Provides the entities of associated log items.
   */
  public function logItems(): array;

}
