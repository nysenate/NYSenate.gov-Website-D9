<?php

namespace Drupal\components\Template;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Loads info about components defined in themes or modules.
 */
class ComponentsRegistry {

  use LoggerChannelTrait;

  /**
   * The component registry for every theme.
   *
   * @var array
   *   An array of component registries, keyed by the theme name.
   */
  protected $registry = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ComponentsRegistry object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend for storing the components registry info.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   */
  public function __construct(
    ModuleExtensionList $moduleExtensionList,
    ThemeExtensionList $themeExtensionList,
    ModuleHandlerInterface $moduleHandler,
    ThemeManagerInterface $themeManager,
    CacheBackendInterface $cache,
    FileSystemInterface $fileSystem
  ) {
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->moduleHandler = $moduleHandler;
    $this->themeManager = $themeManager;
    $this->cache = $cache;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Gets the path to the given template.
   *
   * @param string $name
   *   The name of the template.
   *
   * @return null|string
   *   The path to the template, or NULL if not found.
   */
  public function getTemplate(string $name): ?string {
    $themeName = $this->themeManager->getActiveTheme()->getName();
    if (!isset($this->registry[$themeName])) {
      $this->load($themeName);
    }

    return $this->registry[$themeName][$name] ?? NULL;
  }

  /**
   * Ensures the component registry is available for the given active theme.
   *
   * @param string $themeName
   *   The name of the active theme.
   */
  protected function load(string $themeName): void {
    // Load from cache.
    if ($cache = $this->cache->get('components:registry:' . $themeName)) {
      $this->registry[$themeName] = $cache->data;
    }
    else {
      // Build the registry.
      $this->registry[$themeName] = [];

      // Get the full list of namespaces and their paths.
      $nameSpaces = $this->getNamespaces($themeName);

      $regex = '/\.(twig|html|svg)$/';

      foreach ($nameSpaces as $nameSpace => $nameSpacePaths) {
        foreach ($nameSpacePaths as $nameSpacePath) {
          $possible_duplicates = [];
          try {
            // Get a listing of all Twig files in the namespace path.
            $files = $this->fileSystem->scanDirectory($nameSpacePath, $regex);
          }
          catch (NotRegularDirectoryException $exception) {
            $this->logWarning(sprintf('The "@%s" namespace contains a path, "%s", that is not a directory.',
              $nameSpace,
              $nameSpacePath,
            ));
            $files = [];
          }
          ksort($files);
          foreach ($files as $filePath => $file) {
            // Register the full path and short path to the template.
            $templates = [
              '@' . $nameSpace . '/' . str_replace($nameSpacePath . '/', '', $filePath),
              '@' . $nameSpace . '/' . $file->filename,
            ];
            foreach ($templates as $template) {
              if (!isset($this->registry[$themeName][$template])) {
                $this->registry[$themeName][$template] = $filePath;
              }
            }

            // Keep track of duplicates filenames inside this $nameSpacePath.
            $possible_duplicates[$file->filename][] = $filePath;
          }

          // Duplicate template names are expected across separate configured
          // directories in a namespace (e.g. a theme directory vs base theme
          // directory), but duplicates within one configured directory should
          // be warned against.
          foreach ($possible_duplicates as $filename => $paths) {
            if (count($paths) > 1) {
              $extension = substr($filename, strrpos($filename, '.', -1));
              if ($extension !== '.svg') {
                $this->logWarning(sprintf('Found multiple files for the "%s" template; it is recommended to only have one "%s" file in the "%s" namespace’s "%s" directory. Found: %s',
                  '@' . $nameSpace . '/' . $filename,
                  $filename,
                  $nameSpace,
                  $nameSpacePath,
                  implode(', ', $paths)
                ));
              }
            }
          }
        }
      }

      // Only persist if all modules are loaded to ensure the cache is complete.
      if ($this->moduleHandler->isLoaded()) {
        $this->cache->set(
          'components:registry:' . $themeName,
          $this->registry[$themeName],
          Cache::PERMANENT,
          ['theme_registry']
        );
      }
    }
  }

  /**
   * Get namespaces for the given theme.
   *
   * @param string $themeName
   *   The machine name of the theme.
   *
   * @return array
   *   The array of namespaces.
   */
  public function getNamespaces(string $themeName): array {
    if ($cached = $this->cache->get('components:namespaces:' . $themeName)) {
      return $cached->data;
    }

    // Load and cache un-altered Twig namespaces for all themes.
    if ($cached = $this->cache->get('components:namespaces')) {
      $allNamespaces = $cached->data;
    }
    else {
      $allNamespaces = $this->findNamespaces($this->moduleExtensionList, $this->themeExtensionList);
      // Only persist if all modules are loaded to ensure the cache is complete.
      if ($this->moduleHandler->isLoaded()) {
        $this->cache->set(
          'components:namespaces',
          $allNamespaces,
          Cache::PERMANENT,
          ['theme_registry']
        );
      }
    }

    // Get the un-altered namespaces for the theme.
    $namespaces = $allNamespaces[$themeName] ?? [];

    // Run hook_components_namespaces_alter().
    $this->moduleHandler->alter('components_namespaces', $namespaces, $themeName);
    $this->themeManager->alter('components_namespaces', $namespaces, $themeName);

    // Only persist if all modules are loaded to ensure the cache is complete.
    if ($this->moduleHandler->isLoaded()) {
      $this->cache->set(
        'components:namespaces:' . $themeName,
        $namespaces,
        Cache::PERMANENT,
        ['theme_registry']
      );
    }

    return $namespaces;
  }

  /**
   * Finds namespaces for all installed themes.
   *
   * Templates in namespaces will be loaded from paths in this priority:
   * 1. active theme
   * 2. active theme's base themes
   * 3. modules:
   *    a. non-default namespaces
   *    b. default namespaces.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   *
   * @return array
   *   An array of namespaces lists, keyed for each installed theme.
   */
  protected function findNamespaces(ModuleExtensionList $moduleExtensionList, ThemeExtensionList $themeExtensionList): array {
    $moduleInfo = $this->normalizeExtensionListInfo($moduleExtensionList);
    $themeInfo = $this->normalizeExtensionListInfo($themeExtensionList);

    $protectedNamespaces = $this->findProtectedNamespaces($moduleInfo + $themeInfo);

    // Collect module namespaces since they are valid for any active theme.
    $moduleNamespaces = [];

    // Find default namespaces for modules.
    foreach ($moduleInfo as $defaultName => &$info) {
      if (isset($info['namespaces'][$defaultName])) {
        $moduleNamespaces[$defaultName] = $info['namespaces'][$defaultName];
        unset($info['namespaces'][$defaultName]);
      }
    }

    // Find other namespaces defined by modules.
    foreach ($moduleInfo as &$info) {
      foreach ($info['namespaces'] as $namespace => $paths) {
        // Skip protected namespaces and log a warning.
        if (isset($protectedNamespaces[$namespace])) {
          $extensionInfo = $protectedNamespaces[$namespace];
          $this->logWarning(sprintf('The %s module attempted to alter the protected Twig namespace, %s, owned by the %s %s. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.', $info['extensionInfo']['name'], $namespace, $extensionInfo['name'], $extensionInfo['type']));
        }
        else {
          $moduleNamespaces[$namespace] = !isset($moduleNamespaces[$namespace])
            ? $paths
            : array_merge($paths, $moduleNamespaces[$namespace]);
        }
      }
    }

    // Remove protected namespaces from each theme's namespaces and log a
    // warning.
    foreach ($themeInfo as &$info) {
      foreach (array_keys($info['namespaces']) as $namespace) {
        if (isset($protectedNamespaces[$namespace])) {
          unset($info['namespaces'][$namespace]);
          $extensionInfo = $protectedNamespaces[$namespace];
          $this->logWarning(sprintf('The %s theme attempted to alter the protected Twig namespace, %s, owned by the %s %s. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.', $info['extensionInfo']['name'], $namespace, $extensionInfo['name'], $extensionInfo['type']));
        }
      }
    }

    // Build the full list of namespaces for each theme.
    $namespaces = [];
    foreach (array_keys($themeInfo) as $activeTheme) {
      $namespaces[$activeTheme] = $moduleNamespaces;
      foreach (array_merge($themeInfo[$activeTheme]['extensionInfo']['baseThemes'], [$activeTheme]) as $themeName) {
        foreach ($themeInfo[$themeName]['namespaces'] as $namespace => $paths) {
          $namespaces[$activeTheme][$namespace] = !isset($namespaces[$activeTheme][$namespace])
            ? $paths
            : array_merge($paths, $namespaces[$activeTheme][$namespace]);
        }
      }
    }

    return $namespaces;
  }

  /**
   * Gets info from the given extension list and normalizes components data.
   *
   * If a namespace's path starts with a "/", the path is relative to the root
   * Drupal installation path (i.e. the directory that contains Drupal's "core"
   * directory.) Otherwise, the path is relative to the extension's path.
   *
   * @param \Drupal\Core\Extension\ExtensionList $extensionList
   *   The extension list to search.
   *
   * @return array
   *   Components-related info for all extensions in the extension list.
   */
  protected function normalizeExtensionListInfo(ExtensionList $extensionList): array {
    $data = [];

    $themeExtensions = method_exists($extensionList, 'getBaseThemes') ? $extensionList->getList() : [];
    foreach ($extensionList->getAllInstalledInfo() as $name => $extensionInfo) {
      $data[$name] = [
        // Save information about the extension.
        'extensionInfo' => [
          'name' => $extensionInfo['name'],
          'type' => $extensionInfo['type'],
          'package' => $extensionInfo['package'] ?? '',
        ],
      ];
      if (method_exists($extensionList, 'getBaseThemes')) {
        $data[$name]['extensionInfo']['baseThemes'] = [];
        foreach ($extensionList->getBaseThemes($themeExtensions, $name) as $baseTheme => $baseThemeName) {
          // If NULL is given as the name of any base theme, then Drupal
          // encountered an error trying to find the base themes. If this
          // happens for an active theme, Drupal will throw a fatal error. But
          // this may happen for a non-active, installed theme and the
          // components module should simply ignore the broken base theme since
          // the error won't affect the active theme.
          if (!is_null($baseThemeName)) {
            $data[$name]['extensionInfo']['baseThemes'][] = $baseTheme;
          }
        }
      }

      $info = isset($extensionInfo['components']) && is_array($extensionInfo['components'])
        ? $extensionInfo['components']
        : [];

      // Normalize namespace data.
      $data[$name]['namespaces'] = [];
      if (isset($info['namespaces'])) {
        $extensionPath = $extensionList->getPath($name);
        foreach ($info['namespaces'] as $namespace => $paths) {
          // Allow paths to be an array or a string.
          if (!is_array($paths)) {
            $paths = [$paths];
          }

          // Add the full path to the namespace paths.
          foreach ($paths as $key => $path) {
            // Determine if the given path is relative to the Drupal root or to
            // the extension.
            if ($path[0] === '/') {
              // Just remove the starting "/" to make it relative to the Drupal
              // root.
              $paths[$key] = ltrim($path, '/');
            }
            else {
              // $extensionPath is relative to the Drupal root.
              $paths[$key] = $extensionPath . '/' . $path;
            }
          }

          $data[$name]['namespaces'][$namespace] = $paths;
        }
      }

      // Find default namespace flag.
      $data[$name]['allow_default_namespace_reuse'] = isset($info['allow_default_namespace_reuse']);
    }

    return $data;
  }

  /**
   * Finds protected namespaces.
   *
   * @param array $extensionInfo
   *   The array of extensions in the format returned by
   *   normalizeExtensionListInfo().
   *
   * @return array
   *   The array of protected namespaces.
   */
  protected function findProtectedNamespaces(array $extensionInfo): array {
    $protectedNamespaces = [];

    foreach ($extensionInfo as $defaultName => $info) {
      // The extension opted-in to having its default namespace be reusable.
      if ($info['allow_default_namespace_reuse']) {
        continue;
      }

      // The extension is defining its default namespace; other extensions are
      // allowed to add paths to it.
      if (!empty($info['namespaces'][$defaultName])) {
        continue;
      }

      // All other default namespaces are protected.
      $protectedNamespaces[$defaultName] = [
        'name' => $info['extensionInfo']['name'],
        'type' => $info['extensionInfo']['type'],
        'package' => $info['extensionInfo']['package'] ?? '',
      ];
    }

    // Run hook_protected_twig_namespaces_alter().
    $this->moduleHandler->alter('protected_twig_namespaces', $protectedNamespaces);
    $this->themeManager->alter('protected_twig_namespaces', $protectedNamespaces);

    return $protectedNamespaces;
  }

  /**
   * Logs exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param string $message
   *   The warning to log.
   * @param array $context
   *   Any additional context to pass to the logger.
   *
   * @internal
   */
  protected function logWarning(string $message, array $context = []): void {
    $this->getLogger('components')->warning($message, $context);
  }

}
