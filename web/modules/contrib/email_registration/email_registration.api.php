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
 * Implement, to alter the username generation of the email_registration module.
 *
 * Note, that the username is only set, if it differentiates from the original
 * username. Furthermore both "email_registration" as well as
 * "email_registration_username" implement this hook. Beware of the module hook
 * execution order, if you are implementing the hook inside your own module:
 * https://www.drupal.org/docs/develop/creating-modules/understanding-hooks#s-module-hook-execution-order.
 *
 * @param string $newAccountName
 *   The new account name.
 * @param \Drupal\user\UserInterface $account
 *   The user object on which the account name is being altered.
 */
function hook_email_registration_name_alter(string &$newAccountName, UserInterface $account): void {
  // Your hook implementation should ensure that the resulting string
  // works as a username.
  // Note for a more complex example implementation, check out the main
  // module's or submodule's hook implementation.
  $newAccountName = 'My-random-username-' . rand(1, 999);
}

/**
 * @} End of "addtogroup hooks".
 */
