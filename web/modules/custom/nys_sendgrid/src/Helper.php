<?php

namespace Drupal\nys_sendgrid;

use Drupal\reroute_email\Constants\RerouteEmailConstants;
use SendGrid\Mail\Personalization;

/**
 * General helper functions.
 */
abstract class Helper {

  /**
   * Used to detect full name+email addresses.
   *
   * @var string
   */
  protected static string $emailRegex = '/^\s*(.+?)\s*<\s*([^>]+)\s*>$/';

  /**
   * Detects if enabling Sendgrid's sandbox mode is desired.
   *
   * Sandbox mode for SendGrid API requests is desired if:
   * - a Pantheon environment is not found,
   * - OR the Pantheon environment is not "live",
   * - OR rerouting is not enabled.
   *
   * @return bool
   *   If sandbox mode is desired.
   */
  public static function detectSendgridSandbox(): bool {
    $is_live = ($_ENV['PANTHEON_ENVIRONMENT'] ?? '') == 'live';

    return (!($is_live || static::detectMailRerouting()));
  }

  /**
   * Detect if mail rerouting has been enabled.
   *
   * @return bool
   *   TRUE, if the reroute_email module is enabled and configured.
   */
  public static function detectMailRerouting(): bool {
    $cfg = \Drupal::config('reroute_email.settings');
    return \Drupal::service('module_handler')->moduleExists('reroute_email')
        && $cfg->get(RerouteEmailConstants::REROUTE_EMAIL_ENABLE)
        && $cfg->get(RerouteEmailConstants::REROUTE_EMAIL_ADDRESS);
  }

  /**
   * Returns all populated recipients of a SendGrid\Personalization object.
   *
   * @param \SendGrid\Mail\Personalization $personalization
   *   A personalization.
   *
   * @return array
   *   An array of all recipients (To, Cc, and Bcc).
   */
  public static function getAllRecipients(Personalization $personalization): array {
    $temp_to = $personalization->getTos() ?? [];
    $temp_cc = $personalization->getCcs() ?? [];
    $temp_bcc = $personalization->getBccs() ?? [];
    return array_filter($temp_to + $temp_cc + $temp_bcc);
  }

  /**
   * Split an email address into it's name and address components.
   *
   * If $email does not match the detection regex, the return will use the
   * original input as the email, with a blank full name.
   *
   * @param string $email
   *   An RFC-compliant email address "full name <email@domain.com>".
   *
   * @return array
   *   First element is email, the second is full name.
   */
  public static function parseAddress(string $email): array {
    if (preg_match(static::$emailRegex, $email, $matches)) {
      return [$matches[2], $matches[1]];
    }
    else {
      return [$email, ''];
    }
  }

}
