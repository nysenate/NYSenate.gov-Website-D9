<?php

namespace Drupal\name;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a name format.
 */
interface NameListFormatInterface extends ConfigEntityInterface {

  /**
   * Determines if this name format is locked.
   *
   * @return bool
   *   TRUE if the name format is locked, FALSE otherwise.
   */
  public function isLocked();

  /**
   * Get the list settings.
   *
   * @return array
   *   The settings with any custom processing completed.
   */
  public function listSettings();

}
