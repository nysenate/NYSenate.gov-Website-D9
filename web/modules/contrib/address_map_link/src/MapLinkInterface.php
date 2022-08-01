<?php

namespace Drupal\address_map_link;

use Drupal\address\AddressInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Url;

/**
 * Defines an interface for Map link plugins.
 */
interface MapLinkInterface extends PluginInspectionInterface {

  /**
   * Return the name of the map link plugin.
   *
   * @return string
   *   The name of the MapLink plugin.
   */
  public function getName(): string;

  /**
   * Gets the map link url from an address.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getAddressUrl(AddressInterface $address): Url;

}
