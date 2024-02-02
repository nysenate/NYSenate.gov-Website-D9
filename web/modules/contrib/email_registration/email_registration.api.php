<?php

/**
 * @file
 * Documentation for email_registration module API.
 */

use Drupal\user\UserInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Implement this hook to generate a username for the email_registration module.
 *
 * Note: Make sure to implement your own username validation / username unique
 * logic. See the code example below.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user object on which the operation is being performed.
 *
 * @return string|null
 *   A string defining a generated username or NULL to let the main module
 *   do the username generation.
 *
 * @deprecated in email_registration:2.0.0 and is removed from
 * email_registration:3.0.0. Use hook_email_registration_name_alter instead.
 *
 * @see https://www.drupal.org/project/email_registration/issues/3396028
 */
function hook_email_registration_name(UserInterface $account): ?string {
  // Your hook implementation should ensure that the resulting string
  // works as a username. You can use email_registration_cleanup_username($name)
  // to clean up the name.
  $newName = email_registration_cleanup_username('u' . $account->id());
  // Make sure, that your username is unique:
  return email_registration_unique_username($newName, (int) $account->id());
}

/**
 * Implement, to hook into the user presave hook of the main module.
 *
 * E.g. for altering the username generation. For further help, see the
 * implementation example below.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user object on which the operation is being performed.
 */
function hook_email_registration_name_alter(UserInterface &$account): void {
  // Your hook implementation should ensure that the resulting string
  // works as a username. You can use email_registration_cleanup_username($name)
  // to clean up the name.
  $newName = email_registration_cleanup_username('u' . $account->id());
  // Make sure, that your username is unique:
  $newName = email_registration_unique_username($newName, (int) $account->id());
  $account->setUsername($newName);
}

/**
 * @} End of "addtogroup hooks".
 */
