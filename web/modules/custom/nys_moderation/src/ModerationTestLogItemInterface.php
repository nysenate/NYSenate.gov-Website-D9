<?php

namespace Drupal\nys_moderation;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Moderation Test Log Item entities.
 */
interface ModerationTestLogItemInterface extends ContentEntityInterface {

  /**
   * Fetches this item's tested entity.
   */
  public function getTestedEntity(): ContentEntityInterface;

}
