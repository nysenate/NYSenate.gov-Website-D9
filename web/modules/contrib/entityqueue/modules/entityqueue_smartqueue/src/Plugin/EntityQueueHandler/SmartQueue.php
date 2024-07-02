<?php

namespace Drupal\entityqueue_smartqueue\Plugin\EntityQueueHandler;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\EntityQueueInterface;
use Drupal\entityqueue\Plugin\EntityQueueHandler\Multiple;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityQueueHandler(
 *   id = "smartqueue",
 *   title = @Translation("Smart queue"),
 *   description = @Translation("Provides automated subqueues for each entity of a given type."),
 * )
 */
class SmartQueue extends Multiple implements ContainerFactoryPluginInterface {

  /**
   * Provides entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Constructs a new SmartQueue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'entity_type' => '',
      'bundles' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#description' => $this->t('The entity type for which subqueues will be created automatically.'),
      '#options' => $this->entityTypeRepository->getEntityTypeLabels(TRUE),
      '#default_value' => $this->configuration['entity_type'],
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#disabled' => !$this->queue->isNew(),
      '#ajax' => [
        'wrapper' => 'smartqueue-bundle-wrapper',
        'callback' => [get_class($this), 'smartqueueSettingsAjax'],
        'method' => 'replaceWith',
      ]
    ];

    $form_state_values = $form_state->getCompleteFormState()->getValues();
    $entity_type_id = isset($form_state_values['handler_settings_wrapper']) ? $form_state_values['handler_settings_wrapper']['handler_settings']['entity_type'] : $this->configuration['entity_type'];
    if ($bundle_options = $this->getBundleOptions($entity_type_id)) {
      $form['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->entityTypeManager->getDefinition($entity_type_id)->getBundleLabel(),
        '#options' => $bundle_options,
        '#default_value' => (array) $this->configuration['bundles'],
        '#required' => TRUE,
        '#size' => 6,
        '#multiple' => TRUE,
        '#prefix' => '<div id="smartqueue-bundle-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['bundles'] = [
        '#type' => 'value',
        '#value' => [$entity_type_id => $entity_type_id],
        '#prefix' => '<div id="smartqueue-bundle-wrapper">',
        '#suffix' => '</div>',
      ];
    }

    return $form;
  }

  /**
   * Gets the list of bundle options for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An array of bundle labels, keyed by bundle name.
   */
  public function getBundleOptions($entity_type_id) {
    $bundle_options = [];
    if (!$entity_type_id) {
      return $bundle_options;
    }

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($entity_type->hasKey('bundle')) {
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }
      natsort($bundle_options);
    }

    return $bundle_options;
  }

  /**
   * Ajax callback for the queue settings form.
   */
  public static function smartqueueSettingsAjax($form, FormStateInterface $form_state) {
    return $form['handler_settings_wrapper']['handler_settings']['bundles'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
    $this->configuration['bundles'] = $form_state->getValue('bundles');
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
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE) {
    parent::onQueuePostSave($queue, $storage, $update);
    $operations = [];

    // Generate list of subqueues to be deleted, and add batch operations
    // to delete them.
    // 1. Get the existing subqueue ids.
    $subqueue_ids = $this->entityTypeManager->getStorage('entity_subqueue')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('queue', $queue->id())
      ->execute();

    // 2. Get the list of relevant subqueues for this queue.
    $subqueue_id_list = array_map(function ($subqueue_id) use ($queue) {
      return $queue->id() . '__' . $subqueue_id;
    }, $this->getEntityIds());

    // 3. Get a diff of both, so we know which subqueues we don't need anymore.
    $subqueue_diff = array_diff($subqueue_ids, $subqueue_id_list);
    $subqueue_diff_chunks = array_chunk($subqueue_diff, 20);
    foreach ($subqueue_diff_chunks as $subqueue_diff_chunk) {
      $operations[] = [
        [$this, 'deleteSubqueues'],
        [$subqueue_diff_chunk],
      ];
    }

    // Generate list of subqueues to be created, and add batch operations to
    // create them.
    $entity_ids = $this->getEntityIds();
    $entity_id_chunks = array_chunk($entity_ids, 20);
    foreach ($entity_id_chunks as $entity_id_chunk) {
      $operations[] = [
        [$this, 'initSubqueuesCreate'],
        [$queue, $entity_id_chunk, $this->configuration['entity_type']],
      ];
    }

    $batch = [
      'title' => t('Creating/deleting subqueues according to configuration'),
      'operations' => $operations,
      'finished' => [get_class($this), 'initSubqueuesFinished'],
    ];

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage) {
    // Create batch operation to delete all the subqueues when the parent queue is deleted.
    $subqueue_ids = $this->entityTypeManager->getStorage('entity_subqueue')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('queue', $queue->id())
      ->execute();
    $subqueue_id_chunks = array_chunk($subqueue_ids, 20);
    $operations = [];
    foreach ($subqueue_id_chunks as $subqueue_id_chunk) {
      $operations[] = [
        [$this, 'deleteSubqueues'],
        [$subqueue_id_chunk],
      ];
    }

    $batch = [
      'title' => t('Deleting subqueues'),
      'operations' => $operations,
      'finished' => [get_class($this), 'deleteSubqueuesFinished'],
    ];

    batch_set($batch);
  }

  /**
   * Create initial subqueues based on smartqueue configuration.
   */
  public function initSubqueuesCreate($queue, $entity_ids, $entity_type_id, &$context) {
    $subqueue_ids = $this->entityTypeManager->getStorage('entity_subqueue')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('queue', $queue->id())
      ->execute();

    // Add new queues according to configured entity and/or bundle.
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      $new_subqueue_id = $queue->id() . '__' . $entity->id();

      // If a relevant subqueue already exists for this queue, continue.
      if (in_array($new_subqueue_id, $subqueue_ids)) {
        continue;
      }

      // Create the subqueue for this entity.
      $subqueue = EntitySubqueue::create([
        'queue' => $queue->id(),
        'name' => $new_subqueue_id,
        'title' => $entity->label(),
        'langcode' => $queue->language()->getId(),
        'attached_entity' => $entity,
      ]);
      $subqueue->save();
      $context['results'][] = $subqueue->id();
      $context['message'] = $this->t('Created subqueue for entity with @id', ['@id' => $entity->id()]);
    }
  }

  /**
   * Batch finished callback.
   */
  public static function initSubqueuesFinished($success, $result, $operations) {
    if ($success) {
      $message = new TranslatableMarkup('Subqueues successfully initialized.');
    }
    else {
      $message = new TranslatableMarkup('Subqueues could not the created.');
    }
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Deletes a list of subqueues.
   */
  public function deleteSubqueues($subqueue_ids, &$context) {
    $storage = $this->entityTypeManager->getStorage('entity_subqueue');
    $subqueues = $storage->loadMultiple($subqueue_ids);
    $storage->delete($subqueues);

    foreach ($subqueues as $subqueue) {
      $context['message'] = $this->t('Deleted subqueue @id', ['@id' => $subqueue->id()]);
    }
  }

  /**
   * Batch finished callback.
   */
  public static function deleteSubqueuesFinished($success, $result, $operations) {
    if ($success) {
      $message = new TranslatableMarkup('Subqueues successfully deleted.');
    }
    else {
      $message = new TranslatableMarkup('Subqueues could not be deleted.');
    }
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Gets a list of entity IDs for which to create subqueues.
   *
   * @return array
   *   An array of entity IDs.
   */
  public function getEntityIds() {
    $entity_type_id = $this->configuration['entity_type'];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $query = $this->entityTypeManager->getStorage($entity_type_id)
      ->getQuery()
      ->accessCheck(TRUE);

    if ($entity_type->hasKey('bundle') && !empty($this->configuration['bundles'])) {
      $query->condition($entity_type->getKey('bundle'), $this->configuration['bundles'], 'IN');
    }

    return $query->execute();
  }

}
