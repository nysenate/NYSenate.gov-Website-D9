<?php

namespace Drupal\nys_sendgrid;

/**
 * Defines events for nys_sendgrid module.
 */
final class Events {

  /**
   * Name of the event which fires after format() has been invoked.
   */
  const AFTER_FORMAT = 'nys_sendgrid.after.format';

  /**
   * Name of the event which fires after the mail() has finished.
   */
  const AFTER_SEND = 'nys_sendgrid.after.send';

}
