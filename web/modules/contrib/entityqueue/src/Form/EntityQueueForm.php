<?php

namespace Drupal\entityqueue\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\entityqueue\EntityQueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for entity queue edit forms.
 */
class EntityQueueForm extends BundleEntityFormBase {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\entityqueue\EntityQueueInterface
   */
  protected $entity;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity queue handler plugin manager.
   *
   * @var \Drupal\entityqueue\EntityQueueHandlerManager
   */
  protected $entityQueueHandlerManager;

  /**
   * Selection manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.repository'),
      $container->get('plugin.manager.entityqueue.handler'),
      $container->get('plugin.manager.entity_reference_selection')
    );
  }

  /**
   * Constructs a EntityQueueForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $entity_queue_handler_manager
   *   The entity queue handler plugin manager.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The selection plugin manager.
   */
  public function __construct(EntityTypeRepositoryInterface $entity_type_repository, PluginManagerInterface $entity_queue_handler_manager, SelectionPluginManagerInterface $selection_manager) {
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityQueueHandlerManager = $entity_queue_handler_manager;
    $this->selectionManager = $selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $queue = $this->entity;

    $form['#title'] = $this->t('Configure <em>@queue</em> entity queue', [
      '@queue' => $queue->label(),
    ]);

    // Default to nodes as the queue target entity type.
    $target_entity_type_id = $queue->getTargetEntityTypeId() ?: 'node';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $queue->label(),
      '#description' => $this->t('The human-readable name of this entity queue. This name must be unique.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $queue->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => '\Drupal\entityqueue\Entity\EntityQueue::load',
      ],
      '#disabled' => !$queue->isNew(),
    ];

    $handler_plugin = $this->getHandlerPlugin($queue, $form_state);
    $form['handler'] = [
      '#type' => 'radios',
      '#title' => $this->t('Queue type'),
      '#options' => $this->entityQueueHandlerManager->getAllEntityQueueHandlers(),
      '#default_value' => $handler_plugin->getPluginId(),
      '#required' => TRUE,
      '#disabled' => !$queue->isNew(),
      '#ajax' => [
        'callback' => '::settingsAjax',
        'wrapper' => 'entityqueue-handler-settings-wrapper',
        'trigger_as' => ['name' => 'handler_change'],
      ],
    ];
    foreach ($this->entityQueueHandlerManager->getDefinitions() as $handler_id => $definition) {
      if (!empty($definition['description'])) {
        $form['handler'][$handler_id]['#description'] = $definition['description'];
      }
    }

    $form['handler_change'] = [
      '#type' => 'submit',
      '#name' => 'handler_change',
      '#value' => $this->t('Change type'),
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'settingsAjaxSubmit']],
      '#attributes' => ['class' => ['js-hide']],
      '#ajax' => [
        'callback' => '::settingsAjax',
        'wrapper' => 'entityqueue-handler-settings-wrapper',
      ],
    ];

    $form['handler_settings_wrapper'] = [
      '#type' => 'container',
      '#id' => 'entityqueue-handler-settings-wrapper',
      '#tree' => TRUE,
    ];

    $form['handler_settings_wrapper']['handler_settings'] = [];
    $subform_state = SubformState::createForSubform($form['handler_settings_wrapper']['handler_settings'], $form, $form_state);
    if ($handler_settings = $handler_plugin->buildConfigurationForm($form['handler_settings_wrapper']['handler_settings'], $subform_state)) {
      $form['handler_settings_wrapper']['handler_settings'] = $handler_settings + [
        '#type' => 'details',
        '#title' => $this->t('@handler settings', ['@handler' => $handler_plugin->getPluginDefinition()['title']]),
        '#open' => TRUE,
      ];
    }

    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['queue_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Queue settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#group' => 'settings',
    ];
    $form['queue_settings']['size'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#process' => [
        [EntityReferenceItem::class, 'formProcessMergeParent'],
      ],
    ];
    $form['queue_settings']['size']['min_size'] = [
      '#type' => 'number',
      '#size' => 2,
      '#default_value' => $queue->getMinimumSize(),
      '#field_prefix' => $this->t('Restrict this queue to a minimum of'),
    ];
    $form['queue_settings']['size']['max_size'] = [
      '#type' => 'number',
      '#default_value' => $queue->getMaximumSize(),
      '#field_prefix' => $this->t('and a maximum of'),
      '#field_suffix' => $this->t('items.'),
    ];
    $form['queue_settings']['act_as_queue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Act as queue'),
      '#default_value' => $queue->getActAsQueue(),
      '#description' => $this->t('When enabled, adding more than the maximum number of items will remove extra items from the queue.'),
      '#states' => [
        'invisible' => [
          ':input[name="queue_settings[max_size]"]' => ['value' => 0],
        ],
      ],
    ];
    $form['queue_settings']['reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse'),
      '#default_value' => $queue->isReversed(),
      '#description' => $this->t('By default, new items are added to the bottom of the queue. If this option is checked, new items will be added to the top of the queue.'),
    ];

    // We have to duplicate all the code from
    // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::fieldSettingsForm()
    // because field settings forms are not easily embeddable.
    $form['entity_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#group' => 'settings',
      '#weight' => -1,
    ];

    // Get all selection plugins for this entity type.
    $selection_plugins = $this->selectionManager->getSelectionGroups($target_entity_type_id);
    $selection_handlers_options = [];
    foreach (array_keys($selection_plugins) as $selection_group_id) {
      // We only display base plugins (e.g. 'default', 'views', ...) and not
      // entity type specific plugins (e.g. 'default:node', 'default:user',
      // ...).
      if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
        $selection_handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
      }
      elseif (array_key_exists($selection_group_id . ':' . $target_entity_type_id, $selection_plugins[$selection_group_id])) {
        $selection_group_plugin = $selection_group_id . ':' . $target_entity_type_id;
        $selection_handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label']);
      }
    }
    ksort($selection_handlers_options);

    $form['entity_settings']['settings'] = [
      '#type' => 'container',
      '#process' => [
        [EntityReferenceItem::class, 'fieldSettingsAjaxProcess'],
        [EntityReferenceItem::class, 'formProcessMergeParent'],
      ],
      '#element_validate' => [[get_class($this), 'entityReferenceSelectionSettingsValidate']],
    ];

    // @todo It should be up to the queue handler to determine what entity types
    //   are queue-able.
    $form['entity_settings']['settings']['target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of items to queue'),
      '#options' => $this->entityTypeRepository->getEntityTypeLabels(TRUE),
      '#default_value' => $target_entity_type_id,
      '#required' => TRUE,
      '#disabled' => !$queue->isNew(),
      '#size' => 1,
      '#ajax' => TRUE,
      '#limit_validation_errors' => [],
    ];

    $form['entity_settings']['settings']['handler'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference method'),
      '#options' => $selection_handlers_options,
      '#default_value' => $queue->getEntitySettings()['handler'],
      '#required' => TRUE,
      '#ajax' => TRUE,
      '#limit_validation_errors' => [],
    ];
    $form['entity_settings']['settings']['handler_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change handler'),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#submit' => [[EntityReferenceItem::class, 'settingsAjaxSubmit']],
    ];

    $form['entity_settings']['settings']['handler_settings'] = [
      '#type' => 'container',
    ];

    $entity_settings = $queue->getEntitySettings();
    $entity_settings += $entity_settings['handler_settings'];
    unset($entity_settings['handler_settings']);
    $selection_handler = $this->selectionManager->getInstance($entity_settings);
    $form['entity_settings']['settings']['handler_settings'] += $selection_handler->buildConfigurationForm([], $form_state);

    // For entityqueue's purposes, the 'target_bundles' setting of the 'default'
    // selection handler does not have to be required.
    if (isset($form['entity_settings']['settings']['handler_settings']['target_bundles'])) {
      $form['entity_settings']['settings']['handler_settings']['target_bundles']['#required'] = FALSE;
    }

    // Also, the 'auto-create' option is mostly useless and confusing in the
    // entityqueue UI.
    if (isset($form['entity_settings']['settings']['handler_settings']['auto_create'])) {
      $form['entity_settings']['settings']['handler_settings']['auto_create']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * Gets the handler plugin for the currently selected queue handler.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $entity
   *   The current form entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\entityqueue\EntityQueueHandlerInterface
   *   The queue handler plugin.
   */
  protected function getHandlerPlugin(EntityQueueInterface $entity, FormStateInterface $form_state) {
    if (!$handler_plugin = $form_state->get('handler_plugin')) {
      $stored_handler_id = $entity->getHandler();
      // Use selected handler if it exists, falling back to the stored handler.
      $handler_id = $form_state->getValue('handler', $stored_handler_id);
      // If the current handler is the stored handler, use the stored handler
      // settings. Otherwise leave the settings empty.
      $handler_configuration = $handler_id === $stored_handler_id ? $entity->getHandlerConfiguration() : [];

      $handler_plugin = $this->entityQueueHandlerManager->createInstance($handler_id, $handler_configuration);
      $handler_plugin->setQueue($entity);
      $form_state->set('handler_plugin', $handler_plugin);
    }
    return $handler_plugin;
  }

  /**
   * Ajax callback for the queue settings form.
   */
  public static function settingsAjax($form, FormStateInterface $form_state) {
    return $form['handler_settings_wrapper'];
  }

  /**
   * Submit handler for the non-JS case.
   */
  public static function settingsAjaxSubmit($form, FormStateInterface $form_state) {
    $form_state->set('handler_plugin', NULL);
    $form_state->setRebuild();
  }

  /**
   * Form element validation handler; Invokes selection plugin's validation.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function entityReferenceSelectionSettingsValidate(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
    $queue = $form_state->getFormObject()->getEntity();

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection_handler */
    $selection_handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($queue->getEntitySettings());

    // @todo Take care of passing the right $form and $form_state structures to
    // the selection validation method. For now, we just have to duplicate the
    // validation of the 'default' selection plugin.
    $selection_handler->validateConfigurationForm($form, $form_state);

    // If no checkboxes were checked for 'target_bundles', store NULL ("all
    // bundles are referenceable") rather than empty array ("no bundle is
    // referenceable".
    if ($form_state->getValue(['entity_settings', 'handler_settings', 'target_bundles']) === []) {
      $form_state->setValue(['entity_settings', 'handler_settings', 'target_bundles'], NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $handler_plugin = $this->getHandlerPlugin($this->entity, $form_state);
    $subform_state = SubformState::createForSubform($form['handler_settings_wrapper']['handler_settings'], $form, $form_state);
    $handler_plugin->validateConfigurationForm($form['handler_settings_wrapper']['handler_settings'], $subform_state);
  }

  /**
   * Overrides \Drupal\field_ui\Form\EntityDisplayFormBase::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
    $queue = $this->getEntity();
    $handler_plugin = $this->getHandlerPlugin($queue, $form_state);
    $subform_state = SubformState::createForSubform($form['handler_settings_wrapper']['handler_settings'], $form, $form_state);
    $handler_plugin->submitConfigurationForm($form['handler_settings_wrapper']['handler_settings'], $subform_state);

    $queue->setHandlerPlugin($handler_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $queue = $this->entity;
    $status = $queue->save();

    $edit_link = $queue->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addMessage($this->t('The entity queue %label has been updated.', ['%label' => $queue->label()]));
      $this->logger('entityqueue')->notice('The entity queue %label has been updated.', ['%label' => $queue->label(), 'link' => $edit_link]);
    }
    else {
      $this->messenger()->addMessage($this->t('The entity queue %label has been added.', ['%label' => $queue->label()]));
      $this->logger('entityqueue')->notice('The entity queue %label has been added.', ['%label' => $queue->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirectUrl($queue->toUrl('collection'));
  }

}
