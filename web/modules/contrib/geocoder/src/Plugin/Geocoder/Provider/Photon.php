<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Photon geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "photon",
 *   name = "Photon",
 *   handler = "\Geocoder\Provider\Photon\Photon",
 *   arguments = {
 *     "rootUrl" = "https://photon.komoot.de",
 *   }
 * )
 */
class Photon extends ConfigurableProviderUsingHandlerWithAdapterBase {}
