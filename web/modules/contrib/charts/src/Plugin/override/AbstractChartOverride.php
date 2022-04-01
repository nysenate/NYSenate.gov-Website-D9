<?php

namespace Drupal\charts\Plugin\override;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class Chart Settings plugins.
 */
abstract class AbstractChartOverride extends PluginBase implements ChartOverrideInterface, ContainerFactoryPluginInterface {

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
   * Get Chart Settings Name.
   *
   * @return string
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

}
