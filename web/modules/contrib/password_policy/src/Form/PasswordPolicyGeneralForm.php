<?php

namespace Drupal\password_policy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The general settings of the policy not tied to constraints.
 */
class PasswordPolicyGeneralForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_policy_general_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];

    $form['password_reset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password Reset Days'),
      '#description' => $this->t('User password will reset after the selected number of days.  0 days indicates that passwords never expire.'),
      '#default_value' => $policy->getPasswordReset(),
    ];
    $form['send_reset_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email upon password expiring'),
      '#description' => $this->t('If checked, an email will go to each user when their password expires, with a link to the request password reset email page.'),
      '#default_value' => $policy->getPasswordResetEmailValue(),
    ];

    $form['send_pending_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Send pending email days before'),
      '#description' => $this->t('Send password expiration pending email X days before expiration. 0 days indicates this email will not be sent. The box above must also be checked. Separate by comma if sending multiple notifications.'),
      '#default_value' => implode(',', $policy->getPasswordPendingValue()),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    $policy->set('password_reset', $form_state->getValue('password_reset'));
    $policy->set('send_reset_email', $form_state->getValue('send_reset_email'));
    $reminderMails = explode(',', $form_state->getValue('send_pending_email'));
    // Sort mail reminders so we always check reminders from the "closest" to
    // the "largest".
    sort($reminderMails);
    $policy->set('send_pending_email', $reminderMails);
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
