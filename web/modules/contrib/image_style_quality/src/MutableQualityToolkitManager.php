<?php

namespace Drupal\image_style_quality;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Manage setting quality values on different toolkits.
 */
class MutableQualityToolkitManager extends PluginManagerBase implements MutableQualityToolkitManagerInterface {

  protected ConfigFactoryInterface $configFactory;
  protected ?array $activeToolkit = NULL;

  /**
   * Constructs a new MutableQualityToolkitManager instance.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->discovery = new YamlDiscovery('mutable_quality_toolkits', $module_handler->getModuleDirectories());
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveToolkit(): array {
    if ($this->activeToolkit === NULL) {
      $this->activeToolkit = $this->getDefinition($this->configFactory->get('system.image')->get('toolkit'));
    }
    return $this->activeToolkit;
  }

}
