<?php

declare(strict_types=1);

namespace Drupal\email_registration\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Url;
use Drupal\email_registration\UsernameGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The username generator.
   */
  protected UsernameGenerator $usernameGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition, $checkout_flow);
    $instance->configFactory = $container->get('config.factory');
    $instance->usernameGenerator = $container->get(UsernameGenerator::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    $login_with_username = $this->configFactory->get('email_registration.settings')->get('login_with_username');
    $pane_form['returning_customer']['name']['#title'] = $login_with_username ? $this->t('Email address or username') : $this->t('Email address');
    $pane_form['returning_customer']['name']['#description'] = $login_with_username ? $this->t('Enter your email address or username.') : $this->t('Enter your email address.');
    $pane_form['returning_customer']['name']['#element_validate'][] = 'email_registration_user_login_validate';
    $pane_form['returning_customer']['name']['#type'] = $login_with_username ? 'textfield' : 'email';
    $pane_form['returning_customer']['name']['#maxlength'] = Email::EMAIL_MAX_LENGTH;
    $pane_form['returning_customer']['password']['#description'] = $this->t('Enter the password that accompanies your email address.');
    $complete_form['#cache']['tags'][] = 'config:email_registration.settings';

    $pane_form['register']['name']['#type'] = 'value';
    $pane_form['register']['name']['#value'] = $this->usernameGenerator->generateRandomUsername();
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

      $login_no_username = !$this->configFactory
        ->get('email_registration.settings')
        ->get('login_with_username');
      if (empty($user)) {
        // Check if users are allowed to login with username as well.
        if ($login_no_username) {
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
        // We have found a user! Save username on the form state, as that is
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
          if ($login_no_username) {
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

  /**
   * {@inheritDoc}
   */
  protected function canRegisterAfterCheckout() {
    // If the parent pane is enabled, return early:
    if (parent::canRegisterAfterCheckout()) {
      return TRUE;
    }
    $emailRegisterPane = $this->checkoutFlow->getPane('email_registration_completion_registration');
    return $emailRegisterPane->getStepId() != '_disabled';
  }

}
