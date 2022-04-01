<?php

namespace Drupal\node_revision_delete\Utility;

/**
 * Provides donation messages.
 *
 * @ingroup utility
 */
class Donation {

  /**
   * Returns a donation message to print in module pages.
   *
   * @return string
   *   The donation message.
   */
  public static function getDonationText() {
    $url = ['@url' => 'http://paypal.me/adriancid'];
    return '<div class="description">' . t('If you would like to say thanks and support the development of this module, a <a target="_blank" href="@url">donation</a> will be much appreciated.', $url) . '</div>';
  }

}
