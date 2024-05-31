<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Nominatim geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "nominatim",
 *   name = "Nominatim",
 *   handler = "\Geocoder\Provider\Nominatim\Nominatim",
 *   arguments = {
 *     "rootUrl" = "",
 *     "userAgent" = "",
 *     "referer" = ""
 *   }
 * )
 */
class Nominatim extends ConfigurableProviderUsingHandlerWithAdapterBase {}
