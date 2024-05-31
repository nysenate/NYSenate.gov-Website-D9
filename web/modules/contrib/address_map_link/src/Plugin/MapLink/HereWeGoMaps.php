<?php

namespace Drupal\address_map_link\Plugin\MapLink;

use Drupal\address\AddressInterface;
use Drupal\address_map_link\MapLinkBase;
use Drupal\Core\Url;

/**
 * Provides a HERE WeGo maps link type.
 *
 * @MapLink(
 *   id = "here_wego_maps",
 *   name = @Translation("HERE WeGo Maps - Directions")
 * )
 */
class HereWeGoMaps extends MapLinkBase {

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
    return Url::fromUri('https://wego.here.com/directions/drive//' . $this->addressString($address));
  }

}
