<?php

namespace Drupal\charts\Plugin\override;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Charts Override Manager.
 *
 * Provides the Chart Override plugin manager and manages discovery and
 * instantiation of chart settings plugins.
 */
class ChartOverrideManager extends DefaultPluginManager {

  /**
   * Constructor for ChartManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/override', $namespaces, $module_handler,
      'Drupal\charts\Plugin\override\ChartOverrideInterface', 'Drupal\charts\Annotation\ChartOverride');

    // If you want to include override plugins in your own custom module
    // (recommended), then use an alter hook in your custom module's .module
    // file. It could look something like the following, which overrides
    // Highcharts:
    //
    // function MYMODULE_charts_override_plugin_info_alter(array &$definitions){
    //   $definitions['highcharts_overrides']['class'] = 'Drupal\MYMODULE\Plugin\override\MYMODULEOverrides';
    // }
    //
    // then copy charts/modules/charts_highcharts/src/Plugin/override/HighchartsOverrides.php
    // to MYMODULE/src/Plugin/override/MYMODULEOverrides.php and modify,
    // replacing 'highcharts' with your module's name and extending
    // HighchartsOverrides rather than AbstractChartOverride.
    $this->alterInfo('charts_override_plugin_info');
    $this->setCacheBackend($cache_backend, 'charts_override_plugins');
  }

}
