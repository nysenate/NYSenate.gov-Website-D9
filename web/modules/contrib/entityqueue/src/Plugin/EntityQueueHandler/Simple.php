<?php

namespace Drupal\entityqueue\Plugin\EntityQueueHandler;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\EntityQueueHandlerBase;
use Drupal\entityqueue\EntityQueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity queue handler that manages a single subqueue.
 *
 * @EntityQueueHandler(
 *   id = "simple",
 *   title = @Translation("Simple queue"),
 *   description = @Translation("Provides a queue with a single (fixed) subqueue."),
 * )
 */
class Simple extends EntityQueueHandlerBase implements ContainerFactoryPluginInterface {

  use RedirectDestinationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Simple queue handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleSubqueues() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAutomatedSubqueues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueListBuilderOperations() {
    // Simple queues have just one subqueue so we can link directly to the edit
    // form.
    $subqueue = EntitySubqueue::load($this->queue->id());
    $subqueue = $this->entityRepository->getTranslationFromContext($subqueue);
    $operations['edit_subqueue'] = [
      'title' => $this->t('Edit items'),
      'weight' => -9,
      'url' => $subqueue->toUrl('edit-form')->mergeOptions(['query' => $this->getRedirectDestination()->getAsArray()]),
    ];

    // Add a 'Translate' operation if translation is enabled for this queue.
    if ($this->moduleHandler->moduleExists('content_translation') && content_translation_translate_access($subqueue)->isAllowed()) {
      $operations['translate_subqueue'] = [
        'title' => $this->t('Translate subqueue'),
        'url' => $subqueue->toUrl('drupal:content-translation-overview'),
        'weight' => -8,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE) {
    // Make sure that every simple queue has a subqueue.
    if ($update) {
      $subqueue = EntitySubqueue::load($queue->id());
      $subqueue->setTitle($queue->label());
    }
    else {
      $subqueue = EntitySubqueue::create([
        'queue' => $queue->id(),
        'name' => $queue->id(),
        'title' => $queue->label(),
        'langcode' => $queue->language()->getId(),
      ]);
    }

    $subqueue->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {
    // Delete the subqueue when the parent queue is deleted.
    if ($subqueue = EntitySubqueue::load($queue->id())) {
      $subqueue->delete();
    }
  }

}
