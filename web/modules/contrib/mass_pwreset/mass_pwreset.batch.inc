<?php

/**
 * @file
 * Contains batch process functions for resetting passwords.
 */

use Drupal\user\Entity\User as User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Batch process callback.
 *
 * Loads user object, resets password, and optionally sends notify email.
 */
function mass_pwreset_batch_process($data, &$context) {
  // Initiate count values for batch iteration.
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_row'] = 0;
    $context['sandbox']['max'] = count($data['uids']);
  }

  // Establish index for loading user objects.
  $i = $context['sandbox']['current_row'];

  // Reset user password.
  if (isset($data['uids'][$i])) {
    $user = User::load($data['uids'][$i]);
    $user->setPassword(\Drupal::service('password_generator')->generate(40));
    $user->save();

    // Update status message.
    $context['message'] = t('Processing uid: @uid', ['@uid' => $user->id()]);
    $context['results']['passwords_reset'][] = t('User password reset');

    // Send password reset email and log event.
    if ($data['notify_active_users'] == 1) {
      // Set batch message.
      $email_message = t('Password reset email sent to uid: @uid', ['@uid' => $user->id()]);
      $context['message'] = $email_message;

      // Notify active users.
      if (!$user->isBlocked()) {
        _user_mail_notify('password_reset', $user);
        \Drupal::logger('mass_pwreset')->notice($email_message);
      }
      // Notify blocked users if applicable.
      if ($data['notify_blocked_users'] == 1 && $user->isBlocked()) {
        _user_mail_notify('password_reset', $user);
        \Drupal::logger('mass_pwreset')->notice($email_message);
      }
    }

    // Log password reset for this uid.
    $message = t('Password reset for uid: @uid', ['@uid' => $user->id()]);
    \Drupal::logger('mass_pwreset')->notice($message);
  }

  // Update batch progress information.
  $context['sandbox']['progress'] += 1;
  $context['sandbox']['current_row'] += 1;

  // Inform the batch engine of estimated completion level reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  // Save roles in results for finished logging.
  $context['results']['roles'] = $data['roles'];
}

/**
 * Batch finish callback.
 *
 * Logs finished results and set message to user.
 */
function mass_pwreset_batch_finished($success, $results, $operations) {
  if ($success) {
    $passwords_reset = count($results['passwords_reset']);

    // Display message to user.
    $message = t('Mass password reset batch finished resetting @count passwords.', ['@count' => $passwords_reset]);
    \Drupal::messenger()->addMessage($message);

    // Log password reset batch finished.
    $log_message = t('Mass password reset finished. @count passwords reset with roles: @roles', [
      '@count' => $passwords_reset,
      '@roles' => implode(', ', $results['roles']),
    ]);
    \Drupal::logger('mass_pwreset')->notice($log_message);
    return new RedirectResponse('/admin/people/mass-pwreset');
  }
  else {
    // Log password reset batch failure.
    \Drupal::logger('mass_pwreset')->error(t('Mass password reset batch failed.'));
    \Drupal::messenger()->addError(t('Password reset batch has failed.'));
  }
}
