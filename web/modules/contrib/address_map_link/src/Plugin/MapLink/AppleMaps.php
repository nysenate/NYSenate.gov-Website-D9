<?php

namespace Drupal\address_map_link\Plugin\MapLink;

use Drupal\address\AddressInterface;
use Drupal\address_map_link\MapLinkBase;
use Drupal\Core\Url;

/**
 * Provides a Apple Maps link type.
 *
 * @MapLink(
 *   id = "apple_maps",
 *   name = @Translation("Apple Maps (with Google Fallback)")
 * )
 */
class AppleMaps extends MapLinkBase {

  /**
   * Gets the map link url from an address.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getAddressUrl(AddressInterface $address): Url {
    return Url::fromUri('https://maps.apple.com/', ['query' => ['q' => $this->addressString($address)]]);
  }

}
