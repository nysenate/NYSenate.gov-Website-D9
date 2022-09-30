<?php

namespace Drupal\password_policy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class PasswordPolicyValidationManager.
 *
 * Decide whether to display validation and whether to validate a password.
 *
 * @package Drupal\password_policy
 */
class PasswordPolicyValidationManager implements PasswordPolicyValidationManagerInterface {

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The password policy storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $passwordPolicyStorage;

  /**
   * Config for user.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userSettingsConfig;

  /**
   * PasswordPolicyVisibilityManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->passwordPolicyStorage = $entityTypeManager->getStorage('password_policy');
    $this->userSettingsConfig = $configFactory->get('user.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function tableShouldBeVisible() {
    if ($this->currentUser->isAnonymous() && $this->userSettingsConfig->get('verify_mail')) {
      return FALSE;
    }

    $role_applies = $this->passwordPolicyStorage->getQuery()
      ->condition('roles.*', $this->currentUser->getRoles(), 'IN')
      ->condition('show_policy_table', TRUE)
      ->execute();
    return !empty($role_applies);
  }

  /**
   * {@inheritdoc}
   */
  public function validationShouldRun() {
    if ($this->currentUser->isAnonymous() && $this->userSettingsConfig->get('verify_mail')) {
      return FALSE;
    }

    $current_user_roles = $this->currentUser->getRoles();
    // Before a user has registered all they have is the anonymous role,
    // which can't be targeted by a password policy rule. So also search
    // for the authenticated role, which every user will have post register.
    $current_user_roles[] = "authenticated";
    $role_applies = $this->passwordPolicyStorage->getQuery()
      ->condition('roles.*', $current_user_roles, 'IN')
      ->execute();
    return !empty($role_applies);
  }

}
