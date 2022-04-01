<?php

namespace Drupal\webform\Utility;

/**
 * Helper class for webform mail handing.
 */
class WebformMailHelper {

  /**
   * Validate email address.
   *
   * @param string $address
   *   An email address.
   *
   * @return bool
   *   TRUE is email address is valid.
   */
  public static function validateAddress(string $address) {
    if (class_exists('\Symfony\Component\Mime\Address')) {
      try {
        // phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
        \Symfony\Component\Mime\Address::create($address);
        return TRUE;
      }
      catch (\Exception $exception) {
        return FALSE;
      }
    }
    else {
      /** @var \Drupal\Component\Utility\EmailValidatorInterface $email_validator */
      $email_validator = \Drupal::service('email.validator');
      return $email_validator->isValid($address);
    }
  }

  /**
   * Encode email address.
   *
   * @param string $address
   *   An email address.
   * @param string $name
   *   (optional) A name associated with the email address.
   *
   * @return string
   *   Encode email address with an optional name.
   */
  public static function formatAddress(string $address, string $name = '') {
    // Remove less than (<) and greater (>) than from name.
    $name = preg_replace('/[<>]/', '', $name);

    if (class_exists('\Symfony\Component\Mime\Address')
      && class_exists('\Symfony\Component\Mime\Header\MailboxHeader')) {
      // phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
      $mime_address = new \Symfony\Component\Mime\Address($address, $name);
      $mailbox_header = new \Symfony\Component\Mime\Header\MailboxHeader('Temp', $mime_address);
      return $mailbox_header->getBodyAsString();
      // phpcs:enable
    }
    elseif (class_exists('\Drupal\Component\Utility\Mail')) {
      if ($name) {
        // phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
        return \Drupal\Component\Utility\Mail::formatDisplayName($name) . ' <' . $address . '>';
      }
      else {
        return $address;
      }
    }
    else {
      throw new \Exception('Unable to format email address.');
    }
  }

}
