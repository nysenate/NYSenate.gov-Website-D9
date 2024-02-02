<?php

namespace Drupal\password_policy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to reset user passwords by role.
 */
class PasswordReset extends FormBase {

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static($entity_type_manager->getStorage('user_role'), $entity_type_manager->getStorage('user'));
  }

  /**
   * Constructs a new PasswordReset form.
   *
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(RoleStorageInterface $role_storage, UserStorageInterface $user_storage) {
    $this->roleStorage = $role_storage;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_policy_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->roleStorage->loadMultiple() as $role) {
      $options[$role->id()] = $role->label();
    }
    unset($options[AccountInterface::ANONYMOUS_ROLE]);
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Force password reset of selected roles.'),
      '#options' => $options,
    ];
    $form['exclude_myself'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Myself'),
      '#description' => $this->t('Exclude your account if you are included in the roles.'),
      '#default_value' => '1',
    ];
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($roles = array_filter($form_state->getValue('roles'))) {
      foreach ($roles as $key => $role) {
        $roles[$key] = $this->roleStorage->load($role)->label();
      }

      // Authenticated role includes all users so we can ignore all other roles.
      $properties = [];
      if (!array_key_exists(AccountInterface::AUTHENTICATED_ROLE, $roles)) {
        $properties['roles'] = array_keys($roles);
      }
      $users = $this->userStorage->loadByProperties($properties);

      $exclude_myself = ($form_state->getValue('exclude_myself') == '1');
      /** @var \Drupal\user\UserInterface $user */
      foreach ($users as $user) {
        if ($exclude_myself && ($user->id() === $this->currentUser()->id())) {
          continue;
        }
        if ($user->hasRole(AccountInterface::ANONYMOUS_ROLE)) {
          continue;
        }
        $user->set('field_password_expiration', '1');
        $user->save();
      }

      $this->messenger()->addMessage(
        $this->formatPlural(
          count($roles),
          'Reset the %roles role.',
          'Reset the %roles roles.',
          ['%roles' => implode(', ', array_values($roles))]
        )
      );
    }
    else {
      $this->messenger()->addWarning($this->t('No roles selected.'));
    }

    $form_state->setRedirectUrl(new Url('entity.password_policy.collection'));
  }

}
