<?php

namespace Drupal\password_policy\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Editing a constraint within the policy wizard form.
 */
class ConstraintEdit extends FormBase {


  /**
   * Adding a tempstore for the multiple steps of the wizard form.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Plugin manager of the password constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Identifier of the wizard's tempstore.
   *
   * @var string
   */
  protected $tempstoreId = 'password_policy.password_policy';

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
    return new static($container->get('tempstore.shared'), $container->get('plugin.manager.password_policy.password_constraint'));
  }

  /**
   * Overriding the constructor to load in the plugin manager and tempstore.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   The tempstore of the wizard form.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager for the password constraints.
   */
  public function __construct(SharedTempStoreFactory $tempstore, PluginManagerInterface $manager) {
    $this->tempstore = $tempstore;
    $this->manager = $manager;
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
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $constraint_id = NULL, $machine_name = NULL) {
    $this->machineName = $machine_name;
    $cached_values = $this->tempstore->get($this->tempstoreId)->get($this->machineName);
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    if (is_numeric($constraint_id)) {
      $id = $constraint_id;
      $constraint_id = $policy->getConstraint($id);
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
    $cached_values = $this->tempstore->get($this->tempstoreId)->get($this->machineName);
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    $constraints = $policy->getConstraints();
    /** @var \Drupal\password_policy\PasswordConstraintInterface $instance */
    $instance = $form_state->getValue('instance');
    $instance->submitConfigurationForm($form, $form_state);
    if ($form_state->hasValue('id')) {
      $constraints[$form_state->getValue('id')] = $instance->getConfiguration();
    }
    else {
      $constraints[] = $instance->getConfiguration();
    }
    $policy->set('policy_constraints', $constraints);
    $this->tempstore->get($this->tempstoreId)->set($this->machineName, $cached_values);
    $form_state->setRedirect('entity.password_policy.wizard.edit', ['machine_name' => $this->machineName, 'step' => 'constraint']);
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $url = Url::fromRoute('entity.password_policy.wizard.edit', ['machine_name' => $this->machineName, 'step' => 'constraint']);
    $response->addCommand(new RedirectCommand($url->toString()));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
