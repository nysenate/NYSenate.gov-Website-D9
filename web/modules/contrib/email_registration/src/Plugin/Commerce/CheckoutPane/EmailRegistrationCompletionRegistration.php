<?php

declare(strict_types=1);

namespace Drupal\email_registration\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionRegister;
use Drupal\Core\Form\FormStateInterface;
use Drupal\email_registration\UsernameGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The username generator.
   */
  protected UsernameGenerator $usernameGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition, $checkout_flow);
    $instance->usernameGenerator = $container->get(UsernameGenerator::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    // Set a temporary username, which gets overwritten through
    // "email_registration_user_presave()":
    $pane_form['name'] = [
      '#type' => 'hidden',
      '#value' => $this->usernameGenerator->generateRandomUsername(),
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
