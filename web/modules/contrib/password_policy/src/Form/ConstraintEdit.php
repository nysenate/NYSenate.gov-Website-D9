<?php

namespace Drupal\password_policy\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Editing a constraint within the policy form.
 */
class ConstraintEdit extends FormBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Plugin manager of the password constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The Password Policy entity.
   *
   * @var \Drupal\password_policy\Entity\PasswordPolicy
   */
  protected $passwordPolicy;

  /**
   * Machine name of the form step.
   *
   * @var string
   */
  protected $machineName;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.password_policy.password_constraint'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Overriding the constructor to load in the plugin manager.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager for the password constraints.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *   The plugin manager for the password constraints.
   */
  public function __construct(PluginManagerInterface $manager, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->manager = $manager;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'password_policy_constraint_edit_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $constraint_id
   *   Plugin ID of the constraint.
   * @param string $machine_name
   *   Machine name of this form step.
   * @param string $password_policy_id
   *   ID of the password_policy entity.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $constraint_id = NULL, $machine_name = NULL, $password_policy_id = NULL) {
    // Set the password_policy entity.
    if (!isset($password_policy_id)) {
      $password_policy_id = $machine_name;
    }
    else {
      $password_policy_id = $this->routeMatch->getParameter('password_policy_id');
      $this->machineName = $machine_name;
    }
    $this->passwordPolicy = $this->entityTypeManager->getStorage('password_policy')->loadByProperties(['id' => $password_policy_id])[$password_policy_id];
    if (is_numeric($constraint_id)) {
      $id = $constraint_id;
      $constraint_id = $this->passwordPolicy->getConstraint($id);
      $instance = $this->manager->createInstance($constraint_id['id'], $constraint_id);
    }
    else {
      $instance = $this->manager->createInstance($constraint_id, []);
    }
    /** @var \Drupal\password_policy\PasswordConstraintInterface $instance */
    $form = $instance->buildConfigurationForm($form, $form_state);
    if (isset($id)) {
      // Conditionally set this form element so that we can update or add.
      $form['id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
    }
    $form['instance'] = [
      '#type' => 'value',
      '#value' => $instance,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => [$this, 'ajaxSave'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $instance = $form_state->getValue('instance');
    $instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $constraints = $this->passwordPolicy->getConstraints();
    /** @var \Drupal\password_policy\PasswordConstraintInterface $instance */
    $instance = $form_state->getValue('instance');
    $instance->submitConfigurationForm($form, $form_state);
    if ($form_state->hasValue('id')) {
      $constraints[$form_state->getValue('id')] = $instance->getConfiguration();
    }
    else {
      $constraints[] = $instance->getConfiguration();
    }
    $this->passwordPolicy->set('policy_constraints', $constraints);
    $this->passwordPolicy->save();
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $url = Url::fromRoute('entity.password_policy.edit_form', ['password_policy' => $this->passwordPolicy->id()]);
    $response->addCommand(new RedirectCommand($url->toString()));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
