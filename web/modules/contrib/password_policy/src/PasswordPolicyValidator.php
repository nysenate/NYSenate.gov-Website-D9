<?php

namespace Drupal\password_policy;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Manipulates Password Policy Validator.
 *
 * @package Drupal\password_policy
 */
class PasswordPolicyValidator implements PasswordPolicyValidatorInterface {
  use StringTranslationTrait;

  /**
   * The password constraint plugin manager.
   *
   * @var \Drupal\password_policy\PasswordConstraintPluginManager
   */
  protected $passwordConstraintPluginManager;

  /**
   * The password policy storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $passwordPolicyStorage;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * PasswordPolicyValidator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The password policy storage.
   * @param \Drupal\password_policy\PasswordConstraintPluginManager $passwordConstraintPluginManager
   *   The password constraint plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PasswordConstraintPluginManager $passwordConstraintPluginManager, ModuleHandlerInterface $moduleHandler) {
    $this->passwordConstraintPluginManager = $passwordConstraintPluginManager;
    $this->passwordPolicyStorage = $entityTypeManager->getStorage('password_policy');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePassword(string $password, UserInterface $user, array $edited_user_roles = []): PasswordPolicyValidationReport {
    // Stop before policy-based validation if password exceeds maximum length.
    if (strlen($password) > PasswordInterface::PASSWORD_MAX_LENGTH) {
      return TRUE;
    }

    if (empty($edited_user_roles)) {
      $edited_user_roles = $user->getRoles();
      $edited_user_roles = array_combine($edited_user_roles, $edited_user_roles);
    }

    $valid = TRUE;

    // Run validation.
    $applicable_policies = $this->getApplicablePolicies($edited_user_roles);

    $original_roles = $user->getRoles();
    $original_roles = array_combine($original_roles, $original_roles);

    $force_failure = FALSE;
    if (!empty(array_diff($edited_user_roles, $original_roles)) && $password === '' && !empty($applicable_policies)) {
      // New role has been added and applicable policies are available.
      $force_failure = TRUE;
    }

    $validationReport = new PasswordPolicyValidationReport();
    foreach ($applicable_policies as $policy) {
      $policy_constraints = $policy->getConstraints();

      foreach ($policy_constraints as $constraint) {
        /** @var \Drupal\password_policy\PasswordConstraintInterface $plugin_object */
        $plugin_object = $this->passwordConstraintPluginManager->createInstance($constraint['id'], $constraint);

        // Execute validation.
        $validation = $plugin_object->validate($password, $user);

        if ($valid && $password !== '' && !$validation->isValid()) {
          // Throw error to ensure form will not submit.
          $validationReport->invalidate($validation->getErrorMessage());
        }
        elseif ($force_failure) {
          $validationReport->invalidate($validation->getErrorMessage());
        }
      }
    }

    return $validationReport;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPasswordPolicyConstraintsTableRows(string $password, UserInterface $user, array $edited_user_roles = []): array {
    if (empty($edited_user_roles)) {
      $edited_user_roles = $user->getRoles();
      $edited_user_roles = array_combine($edited_user_roles, $edited_user_roles);
    }

    // Run validation.
    $applicable_policies = $this->getApplicablePolicies($edited_user_roles);

    $original_roles = $user->getRoles();
    $original_roles = array_combine($original_roles, $original_roles);

    $force_failure = FALSE;
    if ($edited_user_roles !== $original_roles && $password === '' && !empty($applicable_policies) && !isset($original_roles['anonymous'])) {
      // New role has been added and applicable policies are available.
      $force_failure = TRUE;
    }

    $policies_table_rows = [];
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    foreach ($applicable_policies as $policy) {
      $policy_constraints = $policy->getConstraints();

      foreach ($policy_constraints as $constraint) {
        /** @var \Drupal\password_policy\PasswordConstraintInterface $plugin_object */
        $plugin_object = $this->passwordConstraintPluginManager->createInstance($constraint['id'], $constraint);

        // Execute validation.
        $validation = $plugin_object->validate($password, $user);
        if (!$force_failure && $validation->isValid()) {
          $status = $this->t('Pass');
        }
        else {
          $message = $validation->getErrorMessage();
          if (empty($message)) {
            $message = $this->t('New role was added or existing password policy changed. Please update your password.');
          }
          $status = $this->t('Fail - @message', ['@message' => $message]);
        }
        $status_class = 'password-policy-constraint-' . ($validation->isValid() && !$force_failure ? 'passed' : 'failed');
        $table_row = [
          'data' => [
            'policy' => $policy->label(),
            'status' => $status,
            'constraint' => $plugin_object->getSummary(),
          ],
          'class' => [$status_class],
        ];
        $policies_table_rows[] = $table_row;
      }
    }

    $this->moduleHandler->alter(
      'password_policy_constraints_table_rows',
      $policies_table_rows
    );

    return $policies_table_rows;
  }

  /**
   * Gets policies applicable to the given roles.
   *
   * @param array $roles
   *   Roles.
   *
   * @return array
   *   Applicable policies.
   */
  protected function getApplicablePolicies(array $roles): array {
    $applicable_policies = [];

    foreach ($roles as $role) {
      if ($role) {
        $role_map = ['roles.' . $role => $role];
        $role_policies = $this->passwordPolicyStorage->loadByProperties($role_map);
        /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
        foreach ($role_policies as $policy) {
          if (!array_key_exists($policy->id(), $applicable_policies)) {
            $applicable_policies[$policy->id()] = $policy;
          }
        }
      }
    }

    return $applicable_policies;
  }

}
