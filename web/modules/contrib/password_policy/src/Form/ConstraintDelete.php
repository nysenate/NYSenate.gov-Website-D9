<?php

namespace Drupal\password_policy\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deleting a constraint from a policy.
 */
class ConstraintDelete extends ConfirmFormBase {

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
   * The machine name of the form step.
   *
   * @var string
   */
  protected $machineName;

  /**
   * ID of the constraint.
   *
   * @var int
   */
  protected $id;

  /**
   * The Password Policy entity.
   *
   * @var \Drupal\password_policy\Entity\PasswordPolicy
   */
  protected $passwordPolicy;

  /**
   * Plugin manager of the password constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

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
   * Constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   Plugin manager of the password constraints.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
    return 'password_policy_constraint_delete_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $machine_name
   *   The machine name of the policy.
   * @param int $id
   *   The ID value of the constraint.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = NULL, $id = NULL) {
    $policy_id = $this->routeMatch->getParameter('password_policy_id');
    $this->passwordPolicy = $this->entityTypeManager->getStorage('password_policy')->loadByProperties(['id' => $policy_id])[$policy_id];
    $this->machineName = $machine_name;
    $this->id = $id;

    $form['#title'] = $this->getQuestion();

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->getDescription()];
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions'] += $this->actions($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $constraints = $this->passwordPolicy->getConstraints();
    unset($constraints[$this->id]);
    $this->passwordPolicy->set('policy_constraints', $constraints);
    $this->passwordPolicy->save();
    $form_state->setRedirect('entity.password_policy.edit_form', ['password_policy' => $this->passwordPolicy->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $context = $this->passwordPolicy->getConstraint($this->id);
    return $this->t('Are you sure you want to delete the @label constraint?', [
      '@label' => $context['id'],
    ]);
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('entity.password_policy.collection');
  }

  /**
   * Provides the action buttons for submitting this form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A set of actions associated with this form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#submit' => [
          [$this, 'submitForm'],
        ],
      ],
      'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
    ];
  }

}
