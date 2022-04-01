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
 * Implement this hook to generate a username for email_registration module.
 *
 * Other modules may implement hook_email_registration_name($account)
 * to generate a username (return a string to be used as the username, NULL
 * to have email_registration generate it).
 *
 * @param \Drupal\user\UserInterface $account
 *   The user object on which the operation is being performed.
 *
 * @return string
 *   A string defining a generated username.
 */
function hook_email_registration_name(UserInterface $account) {
  // Your hook implementation should ensure that the resulting string
  // works as a username. You can use email_registration_cleanup_username($name)
  // to clean up the name.
  return email_registration_cleanup_username('u' . $account->id());
}

/**
 * @} End of "addtogroup hooks".
 */
