<?php

namespace Drupal\entityqueue;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a EntitySubqueue entity.
 */
interface EntitySubqueueInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface, RevisionLogInterface {

  /**
   * Returns the subqueue's parent queue entity.
   *
   * @return \Drupal\entityqueue\EntityQueueInterface
   *   The parent queue entity.
   */
  public function getQueue();

  /**
   * Sets the subqueue's parent queue entity.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The parent queue entity.
   *
   * @return $this
   */
  public function setQueue(EntityQueueInterface $queue);

  /**
   * Adds an entity to a subqueue.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return $this
   */
  public function addItem(EntityInterface $entity);

  /**
   * Removes an entity from a subqueue.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return $this
   */
  public function removeItem(EntityInterface $entity);

  /**
   * Checks whether the subqueue has a given item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object
   *
   * @return bool
   *   TRUE if the item was found, FALSE otherwise.
   */
  public function hasItem(EntityInterface $entity);

  /**
   * Gets the position (delta) of the given subqueue item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The item in the subqueue.
   *
   * @return int|bool
   *   The position of the given item in the subqueue, or FALSE if not found.
   */
  public function getItemPosition(EntityInterface $entity);

  /**
   * Reverses the items of this subqueue.
   *
   * @return $this
   */
  public function reverseItems();

  /**
   * Shuffles the items of this subqueue.
   *
   * @return $this
   */
  public function shuffleItems();

  /**
   * Removes all the items from this subqueue.
   *
   * @return $this
   */
  public function clearItems();

  /**
   * Gets the subqueue title.
   *
   * @return string
   *   Title of the subqueue.
   */
  public function getTitle();

  /**
   * Sets the subqueue title.
   *
   * @param string $title
   *   The subqueue title.
   *
   * @return \Drupal\entityqueue\EntitySubqueueInterface
   *   The called subqueue entity.
   */
  public function setTitle($title);

  /**
   * Gets the subqueue creation timestamp.
   *
   * @return int
   *   Creation timestamp of the subqueue.
   */
  public function getCreatedTime();

  /**
   * Sets the subqueue creation timestamp.
   *
   * @param int $timestamp
   *   The subqueue creation timestamp.
   *
   * @return \Drupal\entityqueue\EntitySubqueueInterface
   *   The called subqueue entity.
   */
  public function setCreatedTime($timestamp);

}
