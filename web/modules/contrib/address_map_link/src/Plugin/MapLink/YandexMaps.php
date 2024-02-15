<?php

namespace Drupal\address_map_link\Plugin\MapLink;

use Drupal\address\AddressInterface;
use Drupal\address_map_link\MapLinkBase;
use Drupal\Core\Url;

/**
 * Provides a Yandex Maps link type.
 *
 * @MapLink(
 *   id = "yandex_maps",
 *   name = @Translation("Yandex Maps")
 * )
 */
class YandexMaps extends MapLinkBase {

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
    return Url::fromUri(
      'https://yandex.com/maps/',
      [
        'query' => [
          'mode' => 'search',
          'text' => $this->addressString($address),
        ],
      ]
    );
  }

}
