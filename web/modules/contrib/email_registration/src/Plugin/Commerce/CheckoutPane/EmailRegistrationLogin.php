<?php

namespace Drupal\email_registration\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\CredentialsCheckFloodInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Provides the email registration login pane.
 *
 * @CommerceCheckoutPane(
 *   id = "email_registration_login",
 *   label = @Translation("Login with email registration or continue as guest"),
 *   default_step = "_disabled",
 * )
 */
class EmailRegistrationLogin extends Login {

  /**
   * Constructs a new EmailRegistrationLogin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\CredentialsCheckFloodInterface $credentials_check_flood
   *   The credentials check flood controller.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The email registration settings.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, CredentialsCheckFloodInterface $credentials_check_flood, AccountInterface $current_user, UserAuthInterface $user_auth, RequestStack $request_stack, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager, $credentials_check_flood, $current_user, $user_auth, $request_stack);

    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('commerce.credentials_check_flood'),
      $container->get('current_user'),
      $container->get('user.auth'),
      $container->get('request_stack'),
      $container->get('config.factory')->get('email_registration.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    $login_with_username = $this->config->get('login_with_username');
    $pane_form['returning_customer']['name']['#title'] = $login_with_username ? $this->t('Email address or username') : $this->t('Email address');
    $pane_form['returning_customer']['name']['#description'] = $login_with_username ? $this->t('Enter your email address or username.') : $this->t('Enter your email address.');
    $pane_form['returning_customer']['name']['#element_validate'][] = 'email_registration_user_login_validate';
    $pane_form['returning_customer']['name']['#type'] = $login_with_username ? 'textfield' : 'email';
    $pane_form['returning_customer']['name']['#maxlength'] = Email::EMAIL_MAX_LENGTH;
    $pane_form['returning_customer']['password']['#description'] = $this->t('Enter the password that accompanies your email address.');
    $complete_form['#cache']['tags'][] = 'config:email_registration.settings';

    $pane_form['register']['name']['#type'] = 'value';
    $pane_form['register']['name']['#value'] = 'email_registration_' . \Drupal::service('password_generator')->generate();
    $pane_form['register']['mail']['#title'] = $this->t('Email');

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $subformId = $pane_form['#parents'][0];
    $values = $form_state->getValue($pane_form['#parents']);
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#op'] === 'login') {
      $mail = $values['returning_customer']['name'];
      if (!empty($mail)) {
        // Try to load the user by mail.
        $user = user_load_by_mail($mail);
      }

      if (empty($user)) {
        // Check if users are allowed to login with username as well.
        if (!$this->config->get('login_with_username')) {
          // Users are not allowed to login with username. Since no user was
          // found with the specified mail address, fail with an error and
          // bail out.
          $user_input = $form_state->getUserInput();
          $query = isset($user_input[$subformId]['returning_customer']['name']) ? ['name' => $user_input[$subformId]['returning_customer']['name']] : [];
          $form_state->setError($pane_form['returning_customer'], $this->t('Unrecognized email address or password. <a href=":password">Forgot your password?</a>', [
            ':password' => Url::fromRoute('user.pass', [], ['query' => $query])
              ->toString(),
          ]));
          return;
        }
      }
      else {
        // We have found an user! Save username on the form state, as that is
        // what the parent class expects in their submit handler.
        $username = $user->getAccountName();
        $form_state->setValue([
          $subformId,
          'returning_customer',
          'name',
        ], $username);

        // Perform several checks for this user account
        // Copied from parent to override error messages.
        $name_element = $pane_form['returning_customer']['name'];
        $password = trim($values['returning_customer']['password']);
        // Generate the "reset password" url.
        $query = !empty($username) ? ['name' => $username] : [];
        $password_url = Url::fromRoute('user.pass', [], ['query' => $query])
          ->toString();

        if (user_is_blocked($username)) {
          $form_state->setError($name_element, $this->t('The account with email address %mail has not been activated or is blocked.', ['%mail' => $mail]));
          return;
        }

        $uid = $this->userAuth->authenticate($username, $password);
        if (!$uid) {
          $this->credentialsCheckFlood->register($this->clientIp, $username);
          // Changing the wrong credentials error message.
          if (!$this->config->get('login_with_username')) {
            $form_state->setError($name_element, $this->t('Unrecognized email address or password. <a href=":password">Forgot your password?</a>', [':password' => $password_url]));
            // Adding return to avoid the parent error when password is empty.
            return;
          }
          else {
            $form_state->setError($name_element, $this->t('Unrecognized username, email, or password. <a href=":url">Have you forgotten your password?</a>', [':url' => $password_url]));
          }
        }
      }
    }

    parent::validatePaneForm($pane_form, $form_state, $complete_form);
  }

}
