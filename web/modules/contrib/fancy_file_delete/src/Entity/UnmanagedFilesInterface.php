<?php

namespace Drupal\fancy_file_delete\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Unmanaged Files entities.
 *
 * @ingroup fancy_file_delete
 */
interface UnmanagedFilesInterface extends ContentEntityInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Unmanaged Files path.
   *
   * @return string
   *   Name of the Unmanaged Files.
   */
  public function getPath();

  /**
   * Sets the Unmanaged Files path.
   *
   * @param string $filename
   *   The Unmanaged Files name.
   *
   * @return \Drupal\fancy_file_delete\Entity\UnmanagedFilesInterface
   *   The called Unmanaged Files entity.
   */
  public function setPath($filename);

  /**
   * Gets the Unmanaged Files creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Unmanaged Files.
   */
  public function getCreatedTime();

  /**
   * Sets the Unmanaged Files creation timestamp.
   *
   * @param int $timestamp
   *   The Unmanaged Files creation timestamp.
   *
   * @return \Drupal\fancy_file_delete\Entity\UnmanagedFilesInterface
   *   The called Unmanaged Files entity.
   */
  public function setCreatedTime($timestamp);
}
