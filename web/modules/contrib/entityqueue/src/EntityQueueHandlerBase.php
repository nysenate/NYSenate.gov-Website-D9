<?php

namespace Drupal\entityqueue;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for EntityQueueHandler plugins.
 */
abstract class EntityQueueHandlerBase extends PluginBase implements EntityQueueHandlerInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity queue that is using this plugin.
   *
   * @var \Drupal\entityqueue\EntityQueueInterface
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * {@inheritdoc}
   */
  public function setQueue(EntityQueueInterface $queue) {
    $this->queue = $queue;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueListBuilderOperations() {
    // Add an operation to list all subqueues by default.
    $operations['view_subqueues'] = [
      'title' => $this->t('View subqueues'),
      'weight' => -9,
      'url' => $this->queue->toUrl('subqueue-list'),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePreSave(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePreDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

  /**
   * {@inheritdoc}
   */
  public function onQueuePostLoad(EntityQueueInterface $queue, EntityStorageInterface $storage) {}

}
