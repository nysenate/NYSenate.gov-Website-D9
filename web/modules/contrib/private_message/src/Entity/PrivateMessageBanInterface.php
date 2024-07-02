<?php

namespace Drupal\private_message\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;

/**
 * The Private Message Ban entity interface.
 *
 * @ingroup private_message
 */
interface PrivateMessageBanInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the Private Message Ban creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Private Message Ban.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Private Message Ban creation timestamp.
   *
   * @param int $timestamp
   *   The Private Message Ban creation timestamp.
   *
   * @return \Drupal\private_message\Entity\PrivateMessageBanInterface
   *   The called Private Message Ban entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets banned user.
   */
  public function getTarget(): User;

  /**
   * Gets target id.
   */
  public function getTargetId(): int;

  /**
   * Sets banned user.
   */
  public function setTarget(AccountInterface $user): self;

}
