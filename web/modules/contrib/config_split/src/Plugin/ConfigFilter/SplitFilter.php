<?php

namespace Drupal\config_split\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\FileSecurity\FileSecurity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a SplitFilter.
 *
 * @ConfigFilter(
 *   id = "config_split",
 *   label = @Translation("Config Split"),
 *   storages = {"config.storage.sync"},
 *   deriver = "\Drupal\config_split\Plugin\ConfigFilter\SplitFilterDeriver"
 * )
 */
class SplitFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The Configuration manager to calculate the dependencies.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $manager;

  /**
   * The storage for the config which is not part of the directory to sync.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $secondaryStorage;

  /**
   * Filter lists shared with filters of new collections.
   *
   * @var \ArrayObject
   */
  protected $filterLists;

  /**
   * Constructs a new SplitFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigManagerInterface $manager
   *   The config manager for retrieving dependent config.
   * @param \Drupal\Core\Config\StorageInterface|null $secondary
   *   The config storage for the blacklisted config.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManagerInterface $manager, StorageInterface $secondary = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $manager;
    $this->secondaryStorage = $secondary;
    $this->filterLists = new \ArrayObject();
  }

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
   * Get the complete split config.
   *
   * @return string[]
   *   The config names.
   */
  public function getBlacklist() {
    if (!isset($this->filterLists['complete_split'])) {
      $this->filterLists['complete_split'] = $this->calculateBlacklist();
    }
    return $this->filterLists['complete_split'];
  }

  /**
   * Get the conditional split config.
   *
   * @return string[]
   *   The config names.
   */
  public function getGraylist() {
    if (!isset($this->filterLists['conditional_split'])) {
      $this->filterLists['conditional_split'] = $this->calculateGraylist();
    }
    return $this->filterLists['conditional_split'];
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    if ($this->secondaryStorage) {
      if ($alternative = $this->secondaryStorage->read($name)) {
        return $alternative;
      }
    }

    if ($name != 'core.extension') {
      return $data;
    }

    $modules = isset($this->configuration['module']) ? $this->configuration['module'] : [];
    $themes = isset($this->configuration['theme']) ? $this->configuration['theme'] : [];

    if ($this->filtered) {
      // When filtering the 'read' operation, we are about to import the sync
      // configuration. The configuration of the filter is the active config,
      // but we are about to decide which modules should be enabled in addition
      // to the ones defined in the primary storage's 'core.extension'.
      // So we need to read the configuration as it will be imported, as the
      // filter configuration could be split off itself.
      $modules = [];
      $themes = [];
      $updated = $this->filtered->read($this->configuration['config_name']);
      if (is_array($updated)) {
        $modules = isset($updated['module']) ? $updated['module'] : $modules;
        $themes = isset($updated['theme']) ? $updated['theme'] : $themes;
      }
    }

    $data['module'] = array_merge($data['module'], $modules);
    $data['theme'] = array_merge($data['theme'], $themes);
    // Sort the modules.
    $sort_modules = $data['module'];
    uksort($sort_modules, function ($a, $b) use ($sort_modules) {
      // Sort by module weight, this assumes the schema of core.extensions.
      if ($sort_modules[$a] != $sort_modules[$b]) {
        return $sort_modules[$a] > $sort_modules[$b] ? 1 : -1;
      }
      // Or sort by module name.
      return $a > $b ? 1 : -1;
    });

    $data['module'] = $sort_modules;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    if (!$this->secondaryStorage) {
      throw new \InvalidArgumentException('The split storage has to be set and exist for write operations.');
    }

    if (in_array($name, $this->getBlacklist())) {
      if ($data) {
        $this->secondaryStorage->write($name, $data);
      }

      return NULL;
    }
    elseif (in_array($name, $this->getGraylist())) {
      if (!$this->configuration['graylist_skip_equal'] || !$this->source || $this->source->read($name) != $data) {
        // The configuration is in the graylist but skip-equal is not set or
        // the source does not have the same data, so write to secondary and
        // return source data or null if it doesn't exist in the source.
        if ($data) {
          $this->secondaryStorage->write($name, $data);
        }

        // If the source has it, return that so it doesn't get changed.
        if ($this->source) {
          return $this->source->read($name);
        }

        return NULL;
      }
    }

    if ($this->secondaryStorage->exists($name)) {
      // If the secondary storage has the file but should not then delete it.
      $this->secondaryStorage->delete($name);
    }

    if ($name != 'core.extension') {
      return $data;
    }

    $data['module'] = array_diff_key($data['module'], $this->configuration['module']);
    $data['theme'] = array_diff_key($data['theme'], $this->configuration['theme']);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWriteEmptyIsDelete($name) {
    return $name != 'core.extension';
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    if (!$exists && $this->secondaryStorage) {
      $exists = $this->secondaryStorage->exists($name);
    }

    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDelete($name, $delete) {
    if ($delete && $this->secondaryStorage && $this->secondaryStorage->exists($name)) {
      // Call delete on the secondary storage anyway.
      $this->secondaryStorage->delete($name);
    }

    if (in_array($name, $this->getGraylist()) && !in_array($name, $this->getBlacklist())) {
      // Do not delete graylisted config.
      return FALSE;
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    if ($this->secondaryStorage) {
      $data = array_merge($data, $this->secondaryStorage->readMultiple($names));
    }

    if (in_array('core.extension', $names)) {
      $data['core.extension'] = $this->filterRead('core.extension', $data['core.extension']);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    if ($this->secondaryStorage) {
      $data = array_unique(array_merge($data, $this->secondaryStorage->listAll($prefix)));
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDeleteAll($prefix, $delete) {
    if ($delete && $this->secondaryStorage) {
      try {
        $this->secondaryStorage->deleteAll($prefix);
      }
      catch (\UnexpectedValueException $exception) {
        // The file storage tries to remove directories of collections. But this
        // fails if the directory doesn't exist. So everything is actually fine.
      }
    }

    if (!empty($this->getGraylist())) {
      // If the split uses the graylist feature delete individually.
      return FALSE;
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    if ($this->secondaryStorage) {
      $filter = new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->manager, $this->secondaryStorage->createCollection($collection));
      // Share the filter lists across collections.
      $filter->filterLists = $this->filterLists;
      return $filter;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    if ($this->secondaryStorage) {
      $collections = array_unique(array_merge($collections, $this->secondaryStorage->getAllCollectionNames()));
    }

    return $collections;
  }

  /**
   * Calculate the blacklist by including dependents and resolving wild cards.
   *
   * @return string[]
   *   The list of configuration to completely split.
   */
  protected function calculateBlacklist() {
    $blacklist = $this->configuration['blacklist'];
    $modules = array_keys($this->configuration['module']);
    if ($modules) {
      $blacklist = array_merge($blacklist, array_keys($this->manager->findConfigEntityDependents('module', $modules)));
    }

    $themes = array_keys($this->configuration['theme']);
    if ($themes) {
      $blacklist = array_merge($blacklist, array_keys($this->manager->findConfigEntityDependents('theme', $themes)));
    }

    $extensions = array_merge([], $modules, $themes);

    if (empty($blacklist) && empty($extensions)) {
      // Early return to short-circuit the expensive calculations.
      return [];
    }

    $blacklist = array_filter($this->manager->getConfigFactory()->listAll(), function ($name) use ($extensions, $blacklist) {
      // Filter the list of config objects since they are not included in
      // findConfigEntityDependents.
      foreach ($extensions as $extension) {
        if (strpos($name, $extension . '.') === 0) {
          return TRUE;
        }
      }

      // Add the config name to the blacklist if it is in the wildcard list.
      return self::inFilterList($name, $blacklist);
    });
    sort($blacklist);
    // Finally merge all dependencies of the blacklisted config.
    $blacklist = array_unique(array_merge($blacklist, array_keys($this->manager->findConfigEntityDependents('config', $blacklist))));
    // Exclude from the complete split what is conditionally split.
    return array_diff($blacklist, $this->getGraylist());
  }

  /**
   * Calculate the graylist by including dependents and resolving wild cards.
   *
   * @return string[]
   *   The list of configuration to conditionally split.
   */
  protected function calculateGraylist() {
    $graylist = $this->configuration['graylist'];

    if (empty($graylist)) {
      // Early return to short-circuit the expensive calculations.
      return [];
    }

    $graylist = array_filter($this->manager->getConfigFactory()->listAll(), function ($name) use ($graylist) {
      // Add the config name to the graylist if it is in the wildcard list.
      return self::inFilterList($name, $graylist);
    });
    sort($graylist);

    if ($this->configuration['graylist_dependents']) {
      // Find dependent configuration and add it to the list.
      $graylist = array_unique(array_merge($graylist, array_keys($this->manager->findConfigEntityDependents('config', $graylist))));
    }

    return $graylist;
  }

  /**
   * Check whether the needle is in the haystack.
   *
   * @param string $name
   *   The needle which is checked.
   * @param string[] $list
   *   The haystack, a list of identifiers to determine whether $name is in it.
   *
   * @return bool
   *   True if the name is considered to be in the list.
   */
  protected static function inFilterList($name, array $list) {
    // Prepare the list for regex matching by quoting all regex symbols and
    // replacing back the original '*' with '.*' to allow it to catch all.
    $list = array_map(function ($line) {
      return str_replace('\*', '.*', preg_quote($line, '/'));
    }, $list);
    foreach ($list as $line) {
      if (preg_match('/^' . $line . '$/', $name)) {
        return TRUE;
      }
    }

    return FALSE;
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
        return new FileStorage($directory);
      }

      return NULL;
    }

    // When the folder is not set use a database.
    return new DatabaseStorage($connection, $connection->escapeTable(strtr($config->getName(), ['.' => '_'])));
  }

}
