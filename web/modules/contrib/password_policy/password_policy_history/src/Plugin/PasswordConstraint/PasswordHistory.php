<?php

namespace Drupal\password_policy_history\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\Core\Database\Database;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Enforces a specific character length for passwords.
 *
 * @PasswordConstraint(
 *   id = "password_policy_history_constraint",
 *   title = @Translation("Password History"),
 *   description = @Translation("Provide restrictions on previously used passwords."),
 *   errorMessage = @Translation("You have used the same password previously and cannot."),
 * )
 */
class PasswordHistory extends PasswordConstraintBase implements ContainerFactoryPluginInterface {

  /**
   * The password service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('password')
    );
  }

  /**
   * Constructs a new PasswordHistory constraint.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Password\PasswordInterface $password_service
   *   The password service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PasswordInterface $password_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->passwordService = $password_service;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $configuration = $this->getConfiguration();
    $validation = new PasswordPolicyValidation();

    if (empty($user->id())) {
      return $validation;
    }

    // Query for users hashes.
    $hashes = Database::getConnection()->select('password_policy_history', 'pph')
      ->fields('pph', ['pass_hash'])
      ->condition('uid', $user->id())
      ->execute()
      ->fetchAll();

    $repeats = 0;
    foreach ($hashes as $hash) {
      if ($this->passwordService->check($password, $hash->pass_hash)) {
        $repeats++;
      }
    }

    if ($repeats > intval($configuration['history_repeats'])) {
      $validation->setErrorMessage($this->t('Password has been reused too many times.  Choose a different password.'));
    }

    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'history_repeats' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['history_repeats'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of allowed repeated passwords'),
      '#description' => $this->t('A value of 0 represents no allowed repeats'),
      '#default_value' => $this->getConfiguration()['history_repeats'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('history_repeats')) or $form_state->getValue('history_repeats') < 0) {
      $form_state->setErrorByName('history_repeats', $this->t('The number of repeated passwords value must be zero or greater.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['history_repeats'] = $form_state->getValue('history_repeats');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Number of allowed repeated passwords: @number-repeats', ['@number-repeats' => $this->configuration['history_repeats']]);
  }

}
