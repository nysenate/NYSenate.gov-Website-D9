<?php

namespace Drupal\moderation_sidebar\Form;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The QuickTransitionForm provides quick buttons for changing transitions.
 */
class QuickTransitionForm extends FormBase {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The moderation state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidation
   */
  protected $validation;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * QuickDraftForm constructor.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validation
   *   The moderation state transition validation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ModerationInformationInterface $moderation_info, StateTransitionValidationInterface $validation, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->moderationInformation = $moderation_info;
    $this->validation = $validation;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moderation_sidebar_quick_transition_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $entity = NULL) {
    // Return an empty form if the user does not have appropriate permissions.
    if (!$entity->access('update')) {
      return [];
    }

    // If this is not the default revision and is the latest translation
    // affected revision, then show a discard draft button.
    if (!$entity->isDefaultRevision() && $entity->isLatestTranslationAffectedRevision()) {
      $form['discard_draft'] = [
        '#type' => 'submit',
        '#id' => 'moderation-sidebar-discard-draft',
        '#value' => $this->t('Discard draft'),
        '#attributes' => [
          'class' => ['moderation-sidebar-link', 'button', 'button--danger'],
        ],
        '#submit' => ['::discardDraft'],
      ];
    }

    // Persist the entity so we can access it in the submit handler.
    $form_state->set('entity', $entity);

    $transitions = $this->validation->getValidTransitions($entity, $this->currentUser());
    $workflow = $this->moderationInformation->getWorkFlowForEntity($entity);
    $disabled_transitions = $this->configFactory()
      ->getEditable('moderation_sidebar.settings')
      ->get('workflows.' . $workflow->id() . '_workflow.disabled_transitions');

    // Exclude self-transitions.
    /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface $current_state */
    $current_state = $this->getModerationState($entity);

    /** @var \Drupal\workflows\TransitionInterface[] $transitions */
    $transitions = array_filter($transitions, function ($transition) use ($current_state) {
      return $transition->to()->id() != $current_state->id();
    });

    $is_transition_enabled = FALSE;
    foreach ($transitions as $transition) {
      // Exclude disabled transitions.
      if (empty($disabled_transitions[$transition->id()])) {
        $form[$transition->id()] = [
          '#type' => 'submit',
          '#id' => $transition->id(),
          '#value' => $transition->label(),
          '#attributes' => [
            'class' => ['moderation-sidebar-link', 'button--primary'],
          ],
        ];
        $is_transition_enabled = TRUE;
      }
    }

    // Show only, if at least one transition is enabled.
    if ($is_transition_enabled) {
      $form['revision_log_toggle'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use custom log message'),
        '#default_value' => FALSE,
        '#attributes' => [
          'class' => ['moderation-sidebar-revision-log-toggle'],
        ],
      ];
      $form['revision_log'] = [
        '#type' => 'textarea',
        '#placeholder' => $this->t('Briefly describe this state change.'),
        '#attributes' => [
          'class' => ['moderation-sidebar-revision-log'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="revision_log_toggle"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Form submission handler to discard the current draft.
   *
   * Technically, there is no way to delete Drafts, but as a Draft is really
   * just the current, non-live revision, we can simply re-save the default
   * revision to get the same end-result.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function discardDraft(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');
    $langcode = $entity->language()->getId();
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $default_revision_id = $this->moderationInformation->getDefaultRevisionId($entity->getEntityTypeId(), $entity->id());
    $default_revision = $storage->loadRevision($default_revision_id);
    $default_revision = $this->entityRepository->getTranslationFromContext($default_revision, $langcode);
    if ($form_state->getValue('revision_log_toggle')) {
      $revision_log = $form_state->getValue('revision_log');
    }
    else {
      $revision_log = $this->t('Used the Moderation Sidebar to discard the current draft');
    }
    $revision = $this->prepareNewRevision($default_revision, $revision_log);
    $revision->save();
    $this->messenger()->addMessage($this->t('The draft has been discarded successfully.'));

    // There is no generic entity route to view a single revision, but we know
    // that the node module support this.
    if ($entity->getEntityTypeId() == 'node') {
      $url = Url::fromRoute('entity.node.revision', [
        'node' => $entity->id(),
        'node_revision' => $entity->getRevisionId(),
      ])->toString();
      $this->messenger()->addMessage($this->t('<a href="@url">You can view an archive of the draft by clicking here.</a>', ['@url' => $url]));
    }

    $form_state->setRedirectUrl($entity->toLink()->getUrl());
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');

    /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface[] $transitions */
    $transitions = $this->validation->getValidTransitions($entity, $this->currentUser());
    // Add custom discard draft transition handled by ::discardDraft.
    $transitions['moderation-sidebar-discard-draft'] = '';

    $element = $form_state->getTriggeringElement();

    if (!isset($transitions[$element['#id']])) {
      $form_state->setError($element, $this->t('Invalid transition selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');

    /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface[] $transitions */
    $transitions = $this->validation->getValidTransitions($entity, $this->currentUser());

    $element = $form_state->getTriggeringElement();

    /** @var \Drupal\content_moderation\ContentModerationState $state */
    $state = $transitions[$element['#id']]->to();
    $state_id = $state->id();

    if ($form_state->getValue('revision_log_toggle')) {
      $revision_log = $form_state->getValue('revision_log');
    }
    else {
      $revision_log = $this->t('Used the Moderation Sidebar to change the state to "@state".', ['@state' => $state->label()]);
    }
    $revision = $this->prepareNewRevision($entity, $revision_log);
    $revision->set('moderation_state', $state_id);
    $revision->save();

    $this->messenger()->addMessage($this->t('The moderation state has been updated.'));

    if (!$this->moderationInformation->hasPendingRevision($entity)) {
      $form_state->setRedirectUrl($entity->toUrl());
    }
    else {
      $form_state->setRedirectUrl($entity->toUrl('latest-version'));
    }
  }

  /**
   * Gets the Moderation State of a given Entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity.
   *
   * @return \Drupal\workflows\StateInterface
   *   The moderation state for the given entity.
   */
  protected function getModerationState(ContentEntityInterface $entity) {
    $state_id = $entity->moderation_state->get(0)->getValue()['value'];
    $workflow = $this->moderationInformation->getWorkFlowForEntity($entity);
    return $workflow->getTypePlugin()->getState($state_id);
  }

  /**
   * Prepares a new revision of a given entity, if applicable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   A revision log message to set.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The moderation state for the given entity.
   */
  protected function prepareNewRevision(EntityInterface $entity, $message) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    if ($storage instanceof ContentEntityStorageInterface) {
      $revision = $storage->createRevision($entity);
      if ($revision instanceof RevisionLogInterface) {
        $revision->setRevisionLogMessage($message);
        $revision->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $revision->setRevisionUserId($this->currentUser()->id());
      }
      return $revision;
    }
    return $entity;
  }

}
