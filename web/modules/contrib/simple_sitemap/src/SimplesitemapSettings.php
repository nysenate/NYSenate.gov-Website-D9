<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Config\ConfigFactory;

/**
 * Class SimplesitemapSettings
 * @package Drupal\simple_sitemap
 */
class SimplesitemapSettings {

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;


  /**
   * SimplesitemapSettings constructor.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns a specific sitemap setting or a default value if setting does not
   * exist.
   *
   * @param string $name
   *  Name of the setting, like 'max_links'.
   *
   * @param mixed $default
   *  Value to be returned if the setting does not exist in the configuration.
   *
   * @return mixed
   *  The current setting from configuration or a default value.
   */
  public function getSetting($name, $default = FALSE) {
    $setting = $this->configFactory
      ->get('simple_sitemap.settings')
      ->get($name);

    return NULL !== $setting ? $setting : $default;
  }

  public function getSettings() {
    return $this->configFactory
      ->get('simple_sitemap.settings')
      ->get();
  }

  /**
   * Stores a specific sitemap setting in configuration.
   *
   * @param string $name
   *  Setting name, like 'max_links'.
   * @param mixed $setting
   *  The setting to be saved.
   *
   * @return $this
   */
  public function saveSetting($name, $setting) {
    $this->configFactory->getEditable('simple_sitemap.settings')
      ->set($name, $setting)->save();

    return $this;
  }
}
