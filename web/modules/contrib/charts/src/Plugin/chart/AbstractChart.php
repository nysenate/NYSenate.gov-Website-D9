<?php

namespace Drupal\charts\Plugin\chart;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class Chart plugins.
 */
abstract class AbstractChart extends PluginBase implements ChartInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * Get Chart Name.
   *
   * @return string
   *   Chart Name.
   */
  public function getChartName() {
    return $this->pluginDefinition['name'];
  }

}
