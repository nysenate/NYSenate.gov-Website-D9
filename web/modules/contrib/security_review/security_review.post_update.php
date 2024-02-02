<?php

/**
 * @file
 * Post update functions for Security Review.
 */

/**
 * Userpassword settings.
 */
function security_review_post_update_userpassword_settings() {
  $config = \Drupal::configFactory()->getEditable('security_review.check.security_review-username_same_as_password');
  $config->set('number_of_users', 100);
  $config->save();
}
