<?php

namespace Drupal\moderation_sidebar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Moderation Sidebar settings for this site.
 */
class ModerationSidebarSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ModerationSidebarSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moderation_sidebar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['moderation_sidebar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('moderation_sidebar.settings');
    $workflows = $this->entityTypeManager->getStorage('workflow')->loadMultiple();

    foreach ($workflows as $key => $workflow) {
      $workflow_form_key = $key . '_workflow';

      $form[$workflow_form_key] = [
        '#type' => 'details',
        '#title' => $this->t('Disabled @workflow transitions', ['@workflow' => $workflow->label()]),
        '#description' => $this->t('Select transitions, which should be disabled in the Moderation Sidebar, when the @workflow workflow is in use.', ['@workflow' => $workflow->label()]),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#parents' => ['workflows', $workflow_form_key],
      ];

      // Create an array with transition ids and labels.
      $transitions = $workflow->getTypePlugin()->getTransitions();
      $transitions = array_map(function ($transition) {
        return $transition->label();
      }, $transitions);

      $form[$workflow_form_key]['disabled_transitions'] = [
        '#type' => 'checkboxes',
        '#options' => $transitions,
        '#default_value' => $config->get('workflows.' . $workflow_form_key . '.disabled_transitions') ?: [],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('moderation_sidebar.settings')
      ->set('workflows', $form_state->getValue('workflows'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
