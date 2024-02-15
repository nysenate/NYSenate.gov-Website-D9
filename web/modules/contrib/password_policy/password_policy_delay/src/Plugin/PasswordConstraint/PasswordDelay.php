<?php

namespace Drupal\password_policy_delay\Plugin\PasswordConstraint;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\UserInterface;

/**
 * Enforces a specific character length for passwords.
 *
 * @PasswordConstraint(
 *   id = "password_policy_delay_constraint",
 *   title = @Translation("Password Delay"),
 *   description = @Translation("Provide delay before password can be reset again."),
 *   errorMessage = @Translation("You cannot reset your password until the delay has as elapsed."),
 * )
 */
class PasswordDelay extends PasswordConstraintBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new PasswordDelay constraint.
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
    $configuration = $this->getConfiguration();
    $delay_hours = intval($configuration['delay']);
    $delay = $delay_hours * 3600;

    $validation = new PasswordPolicyValidation();

    if (empty($user->id())) {
      return $validation;
    }

    // Query for last reset.
    $last_reset = $this->connection->select('user__field_last_password_reset', 'ul')
      ->fields('ul', ['field_last_password_reset_value'])
      ->condition('entity_id', $user->id())
      ->execute()
      ->fetchAll();

    $last_reset = reset($last_reset);

    if ($last_reset) {
      $now_date = new DateTimePlus('now', 'UTC');
      $now_stamp = $now_date->getTimestamp();

      // Last pass reset is saved in UTC time zone.
      $date = DrupalDateTime::createFromFormat("Y-m-d\TH:i:s",
      $last_reset->field_last_password_reset_value, 'UTC');
      $timestamp = $date->getTimestamp();
      $ok_pw_reset_time = $timestamp + $delay;

      if ($ok_pw_reset_time > $now_stamp) {
        $validation->setErrorMessage($this->t('Not enough time has passed
          since this password was last reset.'));
      }
    }
    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'delay' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hours until password can be reset again.'),
      '#description' => $this->t('A value of 0 represents no delay'),
      '#default_value' => $this->getConfiguration()['delay'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('delay')) or $form_state->getValue('delay') < 0) {
      $form_state->setErrorByName('delay', $this->t('The delay value must be zero or greater.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['delay'] = $form_state->getValue('delay');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Delay in hours until password can be reset: @delay', ['@delay' => $this->configuration['delay']]);
  }

}
