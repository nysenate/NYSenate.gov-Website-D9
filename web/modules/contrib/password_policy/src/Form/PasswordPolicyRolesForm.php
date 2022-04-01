<?php

namespace Drupal\password_policy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form to select roles that are associated to the policy.
 */
class PasswordPolicyRolesForm extends FormBase {

  /**
   * Role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static($entity_type_manager->getStorage('user_role'));
  }

  /**
   * Overridden constructor to load the storage.
   *
   * @param \Drupal\user\RoleStorageInterface $storage
   *   Role storage.
   */
  public function __construct(RoleStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_policy_roles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    $options = [];
    foreach ($this->storage->loadMultiple() as $role) {
      $options[$role->id()] = $role->label();
    }
    unset($options[AccountInterface::ANONYMOUS_ROLE]);
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Apply to Roles'),
      '#description' => $this->t('Select Roles to which this policy applies.'),
      '#options' => $options,
      '#default_value' => $policy->getRoles(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    $policy->set('roles', array_filter($form_state->getValue('roles')));
  }

}
