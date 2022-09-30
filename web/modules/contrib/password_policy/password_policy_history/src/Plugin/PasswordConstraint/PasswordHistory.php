<?php

namespace Drupal\password_policy_history\Plugin\PasswordConstraint;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Enforces a limit repeated use of the same password.
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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('password'),
      $container->get('database')
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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PasswordInterface $password_service, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->passwordService = $password_service;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $validation = new PasswordPolicyValidation();

    $uid = $user->id();
    if (empty($uid)) {
      return $validation;
    }

    $hashes = $this->getHashes($uid);

    foreach ($hashes as $hash) {
      if ($this->getPasswordService()->check($password, $hash->pass_hash)) {
        $configuration = $this->getConfiguration();
        if ($configuration['history_repeats'] == 0) {
          $validation->setErrorMessage($this->t('No one of the old passwords can be reused. Choose a different password'));
        }
        $validation->setErrorMessage($this->formatPlural($configuration['history_repeats'], 'The last @count password cannot be reused. Choose a different password.', 'The last @count passwords cannot be reused. Choose a different password.', ['@count' => $configuration['history_repeats']]));
      }
    }

    return $validation;
  }

  /**
   * Get the recent passwords for a given user.
   *
   * Attempt to get the latest password that a user has changed limited by the
   * number of reuses that are configured for the policy.
   *
   * @return array
   *   A result matching all password history hashes for the user.
   */
  protected function getHashes($uid) {
    $configuration = $this->getConfiguration();

    $query = $this->connection->select('password_policy_history', 'pph')
      ->fields('pph', ['pass_hash'])
      ->condition('uid', $uid)
      ->orderBy('timestamp', 'desc');
    if ($configuration['history_repeats'] != 0) {
      $query = $query->range(0, $configuration['history_repeats']);
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Accessor for the password service.
   *
   * @return \Drupal\Core\Password\PasswordInterface
   *   The password interface service.
   */
  public function getPasswordService() {
    return $this->passwordService;
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
      '#title' => $this->t('Number of passwords that will be checked in the user password update history'),
      '#description' => $this->t('A value of 0 represents that the user can not repeat any of the old passwords.'),
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
    return $this->t('Number of passwords that will be checked in the user password update history: @number-repeats', ['@number-repeats' => $this->configuration['history_repeats']]);
  }

}
