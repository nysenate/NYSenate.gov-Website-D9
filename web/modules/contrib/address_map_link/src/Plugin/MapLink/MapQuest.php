<?php

namespace Drupal\address_map_link\Plugin\MapLink;

use Drupal\address\AddressInterface;
use Drupal\address_map_link\MapLinkBase;
use Drupal\Core\Url;

/**
 * Provides a Mapquest link type.
 *
 * @MapLink(
 *   id = "mapquest",
 *   name = @Translation("Mapquest")
 * )
 */
class MapQuest extends MapLinkBase {

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
    return Url::fromUri('https://www.mapquest.com/', ['query' => ['q' => $this->addressString($address)]]);
  }

}
