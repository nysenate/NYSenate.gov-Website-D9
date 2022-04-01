<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Mapbox geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "mapbox",
 *   name = "Mapbox",
 *   handler = "\Geocoder\Provider\Mapbox\Mapbox",
 *   arguments = {
 *     "accessToken" = "",
 *     "country" = ""
 *   }
 * )
 */
class Mapbox extends ConfigurableProviderUsingHandlerWithAdapterBase {}
