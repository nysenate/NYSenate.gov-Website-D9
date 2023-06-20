<?php

namespace Drupal\nys_sendgrid\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * A Sendgrid API client factory.
 */
class SendgridClient {

  /**
   * Instantiates a Sendgrid client configured with the API key.
   */
  public static function getClient(ConfigFactory $config, LoggerChannelFactory $logger): \SendGrid {
    $local_config = $config->get('nys_sendgrid.settings');

    // Post a high-visibility log entry if no API key is set.
    $api_key = $local_config->get('api_key');
    if (!$api_key) {
      $logger->get('nys_sendgrid')
        ->critical('SendgridClient has been called, but no API key is set.');
    }

    // Prepare the other possible options.
    // @todo maybe implement curl_options?  Is there a use case?
    $options = array_filter(
          [
            'host' => $local_config->get('host'),
            'version' => $local_config->get('version'),
            'curl_options' => $local_config->get('curl_options'),
          ]
      );
    $options['verify_ssl'] = $local_config->get('verify_ssl') ?? TRUE;

    return new \SendGrid($api_key, $options);
  }

}
