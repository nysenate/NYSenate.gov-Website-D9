<?php

namespace Drupal\fontawesome;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Icon Manager Service for Font Awesome.
 */
class FontAwesomeManager implements FontAwesomeManagerInterface {

  /**
   * The data cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $dataCache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The file system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a FontAwesomeManager object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $data_cache
   *   The data cache.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system helper.
   */
  public function __construct(CacheBackendInterface $data_cache, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, FileSystemInterface $file_system, ModuleExtensionList $module_extension_list) {
    $this->dataCache = $data_cache;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->fileSystem = $file_system;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * Get categories for a specific icon.
   *
   * @param string $icon_name
   *   The name of the icon to get categories for.
   *
   * @return array
   *   All categories for this icon.
   */
  public function getCategoriesByIcon($icon_name) {
    // Get the categories for this icon.
    $categories = [];
    foreach ($this->getCategories() as $category_name => $data) {
      if (in_array($icon_name, $data['icons'])) {
        $categories[$category_name] = $data['label'];
      }
    }

    return $categories;
  }

  /**
   * Get icons for a specific category.
   *
   * @param string $category_name
   *   The name of the category to get icons for.
   *
   * @return array
   *   All icons for this category.
   */
  public function getIconsByCategory($category_name) {
    // Get the categories for this icon.
    $categories = $this->getCategories();

    // If this is a direct category key, return that.
    if (isset($categories[$category_name])) {
      return $categories[$category_name]['icons'];
    }
    else {
      // A label may have been passed instead of a key.
      foreach ($categories as $categoryData) {
        if ($categoryData['label'] == $category_name) {
          return $categoryData['icons'];
        }
      }
    }

    return [];
  }

  /**
   * Get icons with category data included.
   *
   * @return array
   *   List of all icons.
   */
  public function getIconsWithCategories() {
    // Check for cached icons.
    if (!$icons = $this->dataCache->get('fontawesome.iconcategorylist')) {
      $icons = $this->getIcons();

      // Loop over the icons and add categories to each.
      foreach ($icons as &$icon) {
        // Get the categories for this icon.
        $icon['categories'] = $this->getCategoriesByIcon($icon['name']);
        // Merge the categories into the search terms.
        $icon['search_terms'] = array_values(array_unique($icon['search_terms'] + array_flip($icon['categories'])));
      }

      // Cache the icons array.
      $this->dataCache->set('fontawesome.iconcategorylist', $icons, strtotime('+1 week'), ['fontawesome', 'iconcategorylist']);
    }
    else {
      $icons = $icons->data;
    }

    return $icons;
  }

  /**
   * Get categories.
   *
   * @return array
   *   List of all categories.
   */
  public function getCategories() {
    // Check for cached categories.
    if (!$categories = $this->dataCache->get('fontawesome.categorylist')) {
      // Get the meatadata.
      $categories = $this->getCategoryMetadata();

      // Cache the categories array.
      $this->dataCache->set('fontawesome.categorylist', $categories, strtotime('+1 week'), ['fontawesome', 'categorylist']);
    }
    else {
      $categories = $categories->data;
    }

    return (array) $categories;
  }

  /**
   * Get icons.
   *
   * @return array
   *   List of all icons.
   */
  public function getIcons() {
    // Check for cached icons.
    if (!$icons = $this->dataCache->get('fontawesome.iconlist')) {
      // Parse the metadata file and use it to generate the icon list.
      $icons = [];
      foreach ($this->getMetadata() as $icon) {
        // Determine the icon type - brands behave differently.
        $type = 'solid';
        foreach ($icon['styles'] as $style) {
          if ($style == 'brands') {
            $type = 'brands';
            break;
          }
        }
        // Add the aliases into the search terms.
        if (isset($icon['aliases']['names'])) {
          $icon['search']['terms'] += $icon['aliases']['names'];
        }
        $icons[$icon['name']] = [
          'name' => $icon['name'],
          'type' => $type,
          'label' => $icon['label'],
          'styles' => $icon['styles'],
          'search_terms' => $icon['search']['terms'],
        ];
      }

      // Cache the icons array.
      $this->dataCache->set('fontawesome.iconlist', $icons, strtotime('+1 week'), ['fontawesome', 'iconlist']);
    }
    else {
      $icons = $icons->data;
    }

    return (array) $icons;
  }

  /**
   * Extract metadata for a specific icon.
   *
   * @param string $findIcon
   *   The icon for which we want metadata.
   *
   * @return array|bool
   *   Array containing icons.
   */
  public function getIconMetadata($findIcon) {
    // Parse the metadata file and use it to generate the icon list.
    foreach ($this->getMetadata() as $icon) {
      if ($icon['name'] === $findIcon) {
        // Determine the icon type - brands behave differently.
        $type = 'solid';
        foreach ($icon['styles'] as $style) {
          if ($style == 'brands') {
            $type = 'brands';
            break;
          }
        }
        return [
          'name' => $icon['name'],
          'type' => $type,
          'label' => $icon['label'],
          'styles' => $icon['styles'],
        ];
      }
    }

    return FALSE;
  }

  /**
   * Returns the FontAwesome category metadata file path.
   *
   * @return string
   *   The filepath of the metadata file.
   */
  public function getCategoryMetadataFilepath() {
    // Attempt to load the icons from the local library's metadata if possible.
    $metadataFile = $this->fileSystem->realpath(DRUPAL_ROOT . '/libraries/fontawesome/metadata/categories.yml');
    // If we can't load the local file, use the included module icons file.
    if (!file_exists($metadataFile)) {
      $metadataFile = $this->moduleExtensionList->getPath('fontawesome') . '/metadata/categories.yml';
    }
    return $metadataFile;
  }

  /**
   * Loads the Font Awesome category metadata.
   *
   * @return array
   *   The available FontAwesome category metadata.
   */
  public function getCategoryMetadata() {
    // Allow modules and themes to provide their own icon metadata first. If
    // none are provided, use the default metadata file.
    /** @var \Drupal\Component\Discovery\YamlDiscovery $yaml_discovery */
    $yaml_discovery = new YamlDiscovery('fontawesome.categorylist', $this->themeHandler->getThemeDirectories() + $this->moduleHandler->getModuleDirectories());
    $data = $yaml_discovery->findAll();

    $metadata = [];
    if (!empty($data)) {
      foreach ($data as $categories) {
        $metadata = array_merge_recursive($metadata, $categories);
      }
    }
    else {
      $metadata = Yaml::parse(file_get_contents($this->getCategoryMetadataFilepath()));
    }

    $this->moduleHandler->alter('fontawesome_metadata_categories', $metadata);
    return $metadata;
  }

  /**
   * Returns the FontAwesome icon metadata file path.
   *
   * @return string
   *   The filepath of the metadata file.
   */
  public function getMetadataFilepath() {
    // Attempt to load the icons from the local library's metadata if possible.
    $metadataFile = $this->fileSystem->realpath(DRUPAL_ROOT . '/libraries/fontawesome/metadata/icons.yml');
    // If we can't load the local file, use the included module icons file.
    if (!file_exists($metadataFile)) {
      $metadataFile = drupal_get_path('module', 'fontawesome') . '/metadata/icons.yml';
    }
    return $metadataFile;
  }

  /**
   * Loads the Font Awesome icon metadata.
   *
   * @return array
   *   The available FontAwesome icon metadata.
   */
  public function getMetadata() {
    // Allow modules and themes to provide their own icon metadata first. If
    // none are provided, use the default metadata file.
    /** @var \Drupal\Component\Discovery\YamlDiscovery $yaml_discovery */
    $yaml_discovery = new YamlDiscovery('fontawesome.iconlist', $this->themeHandler->getThemeDirectories() + $this->moduleHandler->getModuleDirectories());
    $data = $yaml_discovery->findAll();

    $metadata = [];
    if (!empty($data)) {
      foreach ($data as $icons) {
        $metadata = array_merge_recursive($metadata, $icons);
      }
    }
    else {
      $metadata = Yaml::parse(file_get_contents($this->getMetadataFilepath()));
    }
    // Store the name in the metadata.
    foreach ($metadata as $name => &$data) {
      $data['name'] = $name;
    }

    $this->moduleHandler->alter('fontawesome_metadata', $metadata);
    return $metadata;
  }

  /**
   * Helper function returns the prefix for an icon based on icon type.
   *
   * @param array $styles
   *   An array of valid styles for the icon.
   * @param string $default
   *   The value to assign here if it's not a brand icon.
   *
   * @return string
   *   A valid prefix for this icon.
   */
  public function determinePrefix(array $styles, $default = 'fas') {
    // Determine the icon style - brands behave differently.
    foreach ($styles as $style) {
      if ($style == 'brands') {
        return 'fab';
      }
    }
    return $default;
  }

}
