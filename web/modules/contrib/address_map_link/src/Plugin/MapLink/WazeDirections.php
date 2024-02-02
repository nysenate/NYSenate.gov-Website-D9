<?php

namespace Drupal\address_map_link\Plugin\MapLink;

use Drupal\address\AddressInterface;
use Drupal\address_map_link\MapLinkBase;
use Drupal\Core\Url;

/**
 * Provides a Waze Map link type.
 *
 * @MapLink(
 *   id = "waze_directions",
 *   name = @Translation("Waze - Directions")
 * )
 */
class WazeDirections extends MapLinkBase {

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
    return Url::fromUri('https://waze.com/ul', ['query' => ['q' => $this->addressString($address)]]);
  }

}
