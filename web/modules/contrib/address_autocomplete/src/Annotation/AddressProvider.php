<?php

namespace Drupal\address_autocomplete\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Address provider item annotation object.
 *
 * @see \Drupal\address_autocomplete\Plugin\AddressProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class AddressProvider extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
