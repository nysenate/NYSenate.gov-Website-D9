<?php

namespace Drupal\address_autocomplete\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Address provider plugins.
 */
interface AddressProviderInterface extends PluginFormInterface, PluginInspectionInterface, ConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * @inheritDoc
   */
  public function processQuery($string);

}
