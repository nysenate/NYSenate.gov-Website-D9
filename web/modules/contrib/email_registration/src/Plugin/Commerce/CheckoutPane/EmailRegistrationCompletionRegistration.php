<?php

namespace Drupal\email_registration\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionRegister;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the registration pane without username.
 *
 * Assumes email_registration is enabled.
 *
 * @CommerceCheckoutPane(
 *   id = "email_registration_completion_registration",
 *   label = @Translation("Email registration guest registration after checkout"),
 * )
 */
class EmailRegistrationCompletionRegistration extends CompletionRegister {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    // Set a temporary username, which gets overwritten through
    // "email_registration_user_presave()":
    $pane_form['name'] = [
      '#type' => 'hidden',
      '#value' => \Drupal::service('email_registration.username_generator')->generateRandomUsername(),
    ];

    // Try and help password managers.
    // https://www.chromium.org/developers/design-documents/form-styles-that-chromium-understands
    $pane_form['email'] = [
      '#type' => 'textfield',
      '#value' => $this->order->getEmail(),
      '#attributes' => [
        'autocomplete' => 'username',
      ],
      '#wrapper_attributes' => [
        'style' => 'display: none;',
      ],
    ];

    return $pane_form;
  }

}
