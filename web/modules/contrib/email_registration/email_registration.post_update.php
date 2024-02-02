<?php

/**
 * @file
 * Post update functions for Email Registration module.
 */

/**
 * Add an option to allow the username on registration form.
 */
function email_registration_post_update_require_username_on_registration() {
  \Drupal::configFactory()->getEditable('email_registration.settings')
    ->set('require_username_on_registration', FALSE)
    ->save();
}
