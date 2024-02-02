<?php

namespace Drupal\image_style_quality;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Plugin manager interface.
 */
interface MutableQualityToolkitManagerInterface extends PluginManagerInterface {

  /**
   * Get the toolkit definition that is currently active on the site.
   *
   * @return array
   *   A plugin definition.
   */
  public function getActiveToolkit(): array;

}
