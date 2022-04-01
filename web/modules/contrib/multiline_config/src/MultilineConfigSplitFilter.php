<?php

namespace Drupal\multiline_config;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\config_split\Plugin\ConfigFilter\SplitFilter;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overridden SplitFilter.
 */
class MultilineConfigSplitFilter extends SplitFilter {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Get the configuration including overrides.
    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $container->get('config.factory')->get($configuration['config_name']);
    // Transfer the configuration values to the configuration array.
    $fields = [
      'module',
      'theme',
      'blacklist',
      'graylist',
      'graylist_dependents',
      'graylist_skip_equal',
    ];
    foreach ($fields as $field) {
      $configuration[$field] = $config->get($field);
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.manager'),
      self::getSecondaryStorage($config, $container->get('database'))
    );
  }

  /**
   * Get the Secondary config storage that the split manages.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration for the split.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for creating a database storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The secondary storage to split to and from.
   */
  protected static function getSecondaryStorage(ImmutableConfig $config, Connection $connection) {
    // Here we could determine to use relative paths etc.
    if ($directory = $config->get('folder')) {
      if (!is_dir($directory)) {
        // If the directory doesn't exist, attempt to create it.
        // This might have some negative consequences but we trust the user to
        // have properly configured their site.
        /* @noinspection MkdirRaceConditionInspection */
        @mkdir($directory, 0777, TRUE);
      }
      // The following is roughly: file_save_htaccess($directory, TRUE, TRUE);
      // But we can't use global drupal functions and we want to write the
      // .htaccess file to ensure the configuration is protected and the
      // directory not empty.
      if (file_exists($directory) && is_writable($directory)) {
        $htaccess_path = rtrim($directory, '/\\') . '/.htaccess';
        if (!file_exists($htaccess_path)) {
          file_put_contents($htaccess_path, FileSecurity::htaccessLines(TRUE));
          @chmod($htaccess_path, 0444);
        }
      }

      if (file_exists($directory) || strpos($directory, 'vfs://') === 0) {
        // Allow virtual file systems even if file_exists is false.
        return new MultilineConfigFileStorage($directory);
      }

      return NULL;
    }

    // When the folder is not set use a database.
    return new DatabaseStorage($connection, $connection->escapeTable(strtr($config->getName(), ['.' => '_'])));
  }

}
