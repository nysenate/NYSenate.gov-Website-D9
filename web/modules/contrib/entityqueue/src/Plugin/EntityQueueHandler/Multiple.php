<?php

namespace Drupal\entityqueue\Plugin\EntityQueueHandler;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entityqueue\EntityQueueHandlerBase;
use Drupal\entityqueue\EntityQueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity queue handler that manages multiple subqueues.
 *
 * @EntityQueueHandler(
 *   id = "multiple",
 *   title = @Translation("Multiple subqueues"),
 *   description = @Translation("Provides the ability to add many subqueues to a single queue."),
 * )
 */
class Multiple extends EntityQueueHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Multiple queue handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleSubqueues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAutomatedSubqueues() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {
    // Delete all the subqueues when the parent queue is deleted.
    $subqueue_storage = $this->entityTypeManager->getStorage('entity_subqueue');

    $subqueues = $subqueue_storage->loadByProperties([$this->entityTypeManager->getDefinition('entity_subqueue')->getKey('bundle') => $queue->id()]);
    $subqueue_storage->delete($subqueues);
  }

}
