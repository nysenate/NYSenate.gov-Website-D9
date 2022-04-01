<?php

namespace Drupal\password_policy\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deleting a constraint from a policy within the wizard.
 */
class ConstraintDelete extends ConfirmFormBase {

  /**
   * Temp store to maintain state between steps of the wizard.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * ID of the tempstore to maintain state for the form wizard.
   *
   * @var string
   */
  protected $tempstoreId = 'password_policy.password_policy';

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('tempstore.shared'));
  }

  /**
   * Constructor that adds the tempstore from the container for wizard.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   The tempstore of the wizard form.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
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
    $this->machineName = $machine_name;
    $this->id = $id;

    $cached_values = $this->tempstore->get($this->tempstoreId)->get($this->machineName);
    $form['#title'] = $this->getQuestion($id, $cached_values);

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
    $cached_values = $this->tempstore->get($this->tempstoreId)->get($this->machineName);
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    $constraints = $policy->getConstraints();
    unset($constraints[$this->id]);
    $policy->set('policy_constraints', $constraints);
    $this->tempstore->get($this->tempstoreId)->set($this->machineName, $cached_values);
    $form_state->setRedirect('entity.password_policy.wizard.edit', ['machine_name' => $this->machineName, 'step' => 'constraint']);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion($id = NULL, $cached_values = NULL) {
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $password_policy */
    $password_policy = $cached_values['password_policy'];
    $context = $password_policy->getConstraint($id);
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
    return new Url('entity.password_policy.wizard.edit', ['machine_name' => $this->machineName, 'step' => 'constraint']);
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
