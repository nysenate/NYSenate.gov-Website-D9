<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Trait to facilitate creating split configurations.
 */
trait SplitTestTrait {

  /**
   * Create a split configuration.
   *
   * @param string $name
   *   The name of the split.
   * @param array $data
   *   The split config data.
   *
   * @return \Drupal\Core\Config\Config
   *   The split config object.
   */
  protected function createSplitConfig(string $name, array $data): Config {
    if (substr($name, 0, strlen('config_split.config_split.')) !== 'config_split.config_split.') {
      // Allow using the id as the config name to keep it short.
      $name = 'config_split.config_split.' . $name;
    }
    // Add default values.
    $data += [
      'folder' => '',
      'module' => [],
      'theme' => [],
      'blacklist' => [],
      'graylist' => [],
      'status' => TRUE,
      'graylist_dependents' => TRUE,
      'graylist_skip_equal' => TRUE,
      'weight' => 0,
    ];
    // Set the id from the name.
    $data['id'] = substr($name, strlen('config_split.config_split.'));
    // Create the config.
    $config = new Config($name, $this->container->get('config.storage'), $this->container->get('event_dispatcher'), $this->container->get('config.typed'));
    $config->initWithData($data)->save();

    return $config;
  }

  /**
   * Get the storage for a split.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The split config.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage.
   */
  protected function getSplitSourceStorage(Config $config): StorageInterface {
    $directory = $config->get('folder');
    if ($directory) {
      return new FileStorage($directory);
    }
    // We don't escape the name, it is tests after all.
    return new DatabaseStorage($this->container->get('database'), strtr($config->getName(), ['.' => '_']));
  }

  /**
   * Get the preview storage for a split.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The split config.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage.
   */
  protected function getSplitPreviewStorage(Config $config): StorageInterface {
    // We cache it in its own memory storage so that it becomes decoupled.
    $memory = new MemoryStorage();
    // For now just get the source, there is no preview yet.
    $this->copyConfig($this->getSplitSourceStorage($config), $memory);
    return $memory;
  }

}
