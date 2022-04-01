<?php

/**
 * @file
 * Defines the API for Security Review.
 */

/*
use Drupal\mymodule\MyCheck;
use Drupal\mymodule\MyOtherCheck;
 */
use Drupal\security_review\Check;

/**
 * Returns the array of security checks the module defines.
 *
 * The checks must extend \Drupal\security_review\Check.
 *
 * @return array
 *   An array of security checks.
 */
function hook_security_review_checks() {
  // Return instances of the defined Checks.
  return [
    /*
    new MyCheck(),
    new MyOtherCheck(),
    */
  ];
}

/**
 * Provides logging capabilities.
 *
 * @param \Drupal\security_review\Check $check
 *   The Check the event is related to.
 * @param string $message
 *   The message.
 * @param array $context
 *   The context of the message.
 * @param int $level
 *   Severity (RfcLogLevel).
 */
function hook_security_review_log(Check $check, $message, array $context, $level) {
  if ($check->getNamespace() == "My Module") {
    // Do something with the information.
  }
}
