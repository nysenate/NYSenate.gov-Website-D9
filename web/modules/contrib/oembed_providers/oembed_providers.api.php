<?php

/**
 * @file
 * Hooks related to the oEmbed Providers module.
 */

/**
 * Alters the list of providers after it is fetched.
 *
 * The provider list is a pre-cache array of oEmbed providers. This hook fires
 * before \Drupal\media\OEmbed\Provider objects are generated from the
 * provider list.
 *
 * @param array $providers
 *   An array of provider definitions, as fetched from the oEmbed providers URL.
 *
 * @see \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator::getAll()
 */
function hook_oembed_providers_alter(array &$providers) {
  // Add a custom provider.
  $providers[] = [
    'provider_name' => 'Custom Provider',
    'provider_url' => 'http://custom-provider.example.com',
    'endpoints' => [
      [
        'schemes' => [
          'http://custom-provider.example.com/id/*',
          'https://custom-provider.example.com/id/*',
        ],
        'url' => 'https://custom-provider.example.com/api/v2/oembed/',
        'discovery' => 'true',
      ],
    ],
  ];
}
