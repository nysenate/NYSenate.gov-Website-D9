<?php

namespace Drupal\entityqueue;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading EntityQueueHandler plugins.
 */
class EntityQueueHandlerPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The entity queue that is using this plugin collection.
   *
   * @var \Drupal\entityqueue\Entity\EntityQueue
   */
  protected $queue;

  /**
   * Constructs a new EntityQueueHandlerPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, EntityQueueInterface $queue) {
    $this->queue = $queue;

    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\entityqueue\EntityQueueHandlerInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);

    $this->pluginInstances[$instance_id]->setQueue($this->queue);
  }

}
