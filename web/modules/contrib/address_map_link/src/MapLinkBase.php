<?php

namespace Drupal\address_map_link;

use Drupal\address\AddressInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Base class for Map link plugins.
 */
abstract class MapLinkBase extends PluginBase implements MapLinkInterface {

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->pluginDefinition['name'];
  }

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
    return Url::fromUri('https://google.com/maps', ['query' => ['q' => $this->addressString($address)]]);
  }

  /**
   * Builds a query for use in an url for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   *
   * @return string
   *   A query string.
   */
  protected function addressString(AddressInterface $address): string {
    $addressParameters = [];

    if ($address->getAddressLine1()) {
      $addressParameters[] = $address->getAddressLine1();
    }

    if ($address->getAddressLine2()) {
      $addressParameters[] = $address->getAddressLine2();
    }

    if ($address->getLocality()) {
      $addressParameters[] = $address->getLocality();
    }

    if ($address->getAdministrativeArea()) {
      $addressParameters[] = $address->getAdministrativeArea();
    }

    if ($address->getDependentLocality()) {
      $addressParameters[] = $address->getDependentLocality();
    }

    if ($address->getPostalCode()) {
      $addressParameters[] = $address->getPostalCode();
    }

    if ($address->getCountryCode()) {
      $addressParameters[] = $address->getCountryCode();
    }
    return implode(' ', $addressParameters);
  }

}
