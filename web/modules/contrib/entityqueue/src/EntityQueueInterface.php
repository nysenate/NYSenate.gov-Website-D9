<?php

namespace Drupal\entityqueue;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a EntityQueue entity.
 */
interface EntityQueueInterface extends ConfigEntityInterface {

  /**
   * Gets the EntityQueueHandler plugin ID.
   *
   * @return string
   */
  public function getHandler();

  /**
   * Gets the handler plugin configuration for this queue.
   *
   * @return mixed[]
   *   The handler plugin configuration.
   */
  public function getHandlerConfiguration();

  /**
   * Sets the EntityQueueHandler.
   *
   * @param string $handler_id
   *   The handler name.
   *
   * @return $this
   */
  public function setHandler($handler_id);

  /**
   * Gets the EntityQueueHandler plugin object.
   *
   * @return EntityQueueHandlerInterface
   */
  public function getHandlerPlugin();

  /**
   * Sets the EntityQueueHandler plugin object.
   *
   * @param \Drupal\entityqueue\EntityQueueHandlerInterface $handler
   *   A queue handler plugin.
   *
   * @return $this
   */
  public function setHandlerPlugin($handler);

  /**
   * Gets the ID of the target entity type.
   *
   * @return string
   *   The target entity type ID.
   */
  public function getTargetEntityTypeId();

  /**
   * Gets the minimum number of items that this queue can hold.
   *
   * @return int
   */
  public function getMinimumSize();

  /**
   * Gets the maximum number of items that this queue can hold.
   *
   * @return int
   */
  public function getMaximumSize();

  /**
   * Returns the behavior of exceeding the maximum number of queue items.
   *
   * If TRUE, when a maximum size is set and it is exceeded, the queue will be
   * truncated to the maximum size by removing items from the front of the
   * queue. However, if the 'reverse' option is TRUE as well, the items that
   * exceed the maximum size will be removed from the top instead.
   *
   * @return bool
   */
  public function getActAsQueue();

  /**
   * Returns the behavior of adding new items to a queue.
   *
   * By default, new items are added to the bottom of the queue. If the
   * 'reverse' setting is set to TRUE, the new items will be added to the top
   * of the queue instead.
   *
   * @return bool
   */
  public function isReversed();

  /**
   * Gets the selection settings used by a subqueue's 'items' reference field.
   *
   * @return array
   *   An array with the following keys:
   *   - target_type: The type of the entities that will be queued.
   *   - handler: The entity reference selection handler that will be used by
   *     the subqueue's 'items' field.
   *   - handler_settings: The entity reference selection handler settings that
   *     will be used by the subqueue's 'items' field.
   */
  public function getEntitySettings();

  /**
   * Gets the queue settings.
   *
   * @return array
   *   An array with the following keys:
   *   - min_size: The minimum number of items that this queue can hold.
   *   - max_size: The maximum number of items that this queue can hold.
   *   - act_as_queue: The behavior of exceeding the maximum number of queue
   *     items.
   *   - reverse: New items will be added to the top of the queue.
   */
  public function getQueueSettings();

  /**
   * Loads one or more queues based on their target entity type.
   *
   * @param string $target_entity_type_id
   *   The target entity type ID.
   *
   * @return static[]
   *   An array of entity queue objects, indexed by their IDs.
   */
  public static function loadMultipleByTargetType($target_entity_type_id);

}
