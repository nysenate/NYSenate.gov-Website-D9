<?php

namespace Drupal\entityqueue;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Provides an interface for an EntityQueueHandler plugin.
 *
 * @see \Drupal\entityqueue\Annotation\EntityQueueHandler
 * @see \Drupal\entityqueue\EntityQueueHandlerManager
 * @see \Drupal\entityqueue\EntityQueueHandlerBase
 * @see plugin_api
 */
interface EntityQueueHandlerInterface extends PluginFormInterface, ConfigurableInterface, PluginInspectionInterface, DerivativeInspectionInterface, DependentPluginInterface {

  /**
   * Sets the entity queue that is using this plugin.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue.
   *
   * @return $this
   */
  public function setQueue(EntityQueueInterface $queue);

  /**
   * Whether or not the handler supports multiple subqueues.
   *
   * @return bool
   */
  public function supportsMultipleSubqueues();

  /**
   * Whether or not the handler contains subqueues with an automated lifecycle.
   *
   * For example, this property controls whether the title of subqueues can be
   * edited, or if they can be created or deleted through the UI or API calls.
   *
   * @return bool
   */
  public function hasAutomatedSubqueues();

  /**
   * Gets this queue handler's list builder operations.
   *
   * @return array
   *   An array of entity operations, as defined by
   *   \Drupal\Core\Entity\EntityListBuilderInterface::getOperations()
   */
  public function getQueueListBuilderOperations();

  /**
   * Acts on an entity queue before the presave hook is invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePreSave(EntityQueueInterface $queue, EntityStorageInterface $storage);

  /**
   * Acts on an entity queue before the insert or update hook is invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param bool $update
   *   TRUE if the queue has been updated, or FALSE if it has been inserted.
   */
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE);

  /**
   * Acts on entity queues before they are deleted and before hooks are invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePreDelete(EntityQueueInterface $queue, EntityStorageInterface $storage);

  /**
   * Acts on deleted entity queues before the delete hook is invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage);

  /**
   * Acts on loaded entity queues.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePostLoad(EntityQueueInterface $queue, EntityStorageInterface $storage);

}
