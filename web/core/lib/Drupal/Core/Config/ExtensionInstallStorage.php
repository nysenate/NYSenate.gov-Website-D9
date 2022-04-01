<?php

namespace Drupal\Core\Config;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\ProfileExtensionList;

/**
 * Storage to access configuration and schema in enabled extensions.
 *
 * @see \Drupal\Core\Config\ConfigInstaller
 * @see \Drupal\Core\Config\TypedConfigManager
 */
class ExtensionInstallStorage extends InstallStorage {

  /**
   * The active configuration store.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Flag to include the profile in the list of enabled modules.
   *
   * @var bool
   */
  protected $includeProfile = TRUE;

  /**
   * The name of the currently active installation profile.
   *
   * In the early installer this value can be NULL.
   *
   * @var string|null
   */
  protected $installProfile;

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::__construct().
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The active configuration store where the list of enabled modules and
   *   themes is stored.
   * @param string $directory
   *   The directory to scan in each extension to scan for files.
   * @param string $collection
   *   The collection to store configuration in.
   * @param bool $include_profile
   *   Whether to include the install profile in extensions to
   *   search and to get overrides from.
   * @param string $profile
   *   The current installation profile.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_list
   *   The list of install profiles.
   */
  public function __construct(StorageInterface $config_storage, $directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION, $include_profile = TRUE, $profile = NULL, ProfileExtensionList $profile_list = NULL) {
    parent::__construct($directory, $collection, $profile_list);
    $this->configStorage = $config_storage;
    $this->includeProfile = $include_profile;
    $this->installProfile = $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->configStorage,
      $this->directory,
      $collection,
      $this->includeProfile,
      $this->installProfile
    );
  }

  /**
   * Returns a map of all config object names and their folders.
   *
   * The list is based on enabled modules and themes. The active configuration
   * storage is used rather than \Drupal\Core\Extension\ModuleHandler and
   *  \Drupal\Core\Extension\ThemeHandler in order to resolve circular
   * dependencies between these services and \Drupal\Core\Config\ConfigInstaller
   * and \Drupal\Core\Config\TypedConfigManager.
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = [];
      $this->folders = $this->getCoreNames() + $this->folders;

      $extensions = $this->configStorage->read('core.extension');
      // @todo Remove this scan as part of https://www.drupal.org/node/2186491
      $listing = new ExtensionDiscovery(\Drupal::root());
      $listing = new ExtensionDiscovery(\Drupal::root(), TRUE, NULL, NULL, $this->profileList);
      if (!empty($extensions['module'])) {
        $modules = $extensions['module'];
        $module_list_scan = $listing->scan('module');
        $module_list = [];
        foreach (array_keys($modules) as $module) {
          if (isset($module_list_scan[$module])) {
            $module_list[$module] = $module_list_scan[$module];
          }
        }
        $this->folders = $this->getComponentNames($module_list) + $this->folders;
      }
      if (!empty($extensions['theme'])) {
        $theme_list_scan = $listing->scan('theme');
        foreach (array_keys($extensions['theme']) as $theme) {
          if (isset($theme_list_scan[$theme])) {
            $theme_list[$theme] = $theme_list_scan[$theme];
          }
        }
        $this->folders = $this->getComponentNames($theme_list) + $this->folders;
      }

      if ($this->includeProfile) {
        // The install profile (and any parent profiles) can override module
        // default configuration. We do this by replacing the config file path
        // from the module/theme with the install profile version if there are
        // any duplicates.
        // @todo Remove as part of https://www.drupal.org/node/2186491
        $this->folders = $this->getComponentNames($this->profileList->getAncestors($this->installProfile)) + $this->folders;
      }
    }
    return $this->folders;
  }

}
