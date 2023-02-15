<?php

namespace Drupal\nys_senators\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Provides access to the microsite themes defined in CSS.
 */
class MicrositeThemes {

  /**
   * The cache key for the compiled theme information.
   */
  const CACHE_NAME = 'nys_senators.microsite_themes';

  /**
   * The maximum age in seconds of the theme compilation.
   */
  const CACHE_MAX_AGE = 86400;

  /**
   * The path to the relevant SCSS file.
   */
  const SCSS_FILE = 'src/patterns/global/colors/_colors.scss';

  /**
   * The regex pattern used to parse color assignments in SCSS.
   */
  const SCSS_PATTERN = '/\\$([A-Za-z_0-9]+)_(%%parts%%): (#[a-fA-F0-9]{6})/';

  /**
   * A default theme to use if anything goes wrong.
   */
  const DEFAULT_THEME = [
    'name' => 'default',
    'lgt' => '#FFFFFF',
    'med' => '#FFFFFF',
    'drk' => '#FFFFFF',
  ];

  /**
   * Drupal's Default Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Drupal's Theme Handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $theme;

  /**
   * Constructor.
   */
  public function __construct(CacheBackendInterface $cache, ThemeHandlerInterface $theme) {
    $this->cache = $cache;
    $this->theme = $theme;
  }

  /**
   * Reads the defined themes from the SCSS file.
   *
   * @return array
   *   In which each key is a theme name, and each value is an array with keys
   *   for each of the theme's properties (including 'name', which is the same
   *   as the key).  The return will always include the 'default' entry.
   *
   * @see static::DEFAULT_THEME
   */
  protected function readScssPalettes(): array {
    // The default theme holds the acceptable parts.
    $theme_parts = implode('|', array_diff(array_keys(static::DEFAULT_THEME), ['name']));
    // Use the discovered parts to build the pattern.
    $pattern = str_replace('%%parts%%', $theme_parts, static::SCSS_PATTERN);

    // Read the file.
    $theme_dir = $this->theme->getTheme($this->theme->getDefault())->getPath();
    $css = file_get_contents($theme_dir . '/' . static::SCSS_FILE) ?: '';

    // Match the file contents against the regex.
    $results = [];
    preg_match_all($pattern, $css, $results);

    // Initialize with the default theme.
    $palettes = ['default' => static::DEFAULT_THEME];
    if (is_array($results) && count($results) == 4) {
      // Compile the results.
      foreach ($results[1] as $key => $val) {
        // Every palette is initialized the default as well.
        if (!array_key_exists($val, $palettes)) {
          $palettes[$val] = array_merge(static::DEFAULT_THEME, ['name' => $val]);
        }
        $palettes[$val][$results[2][$key]] = $results[3][$key];
      }
    }

    return $palettes;
  }

  /**
   * Fetch the array of themes, either from cache, or from the file.
   */
  protected function fetchThemes(): array {
    $themes = $this->cache->get(static::CACHE_NAME)->data ?? [];
    if (!$themes) {
      $themes = $this->rebuild();
    }
    return $themes;
  }

  /**
   * Fetches a single theme by name, or all themes if no name is given.
   */
  public function getTheme(string $name = ''): array {
    return $name
      ? (($this->fetchThemes()[$name]) ?? static::DEFAULT_THEME)
      : $this->fetchThemes();
  }

  /**
   * Rebuilds the cache entry for the compiled array of themes.
   */
  public function rebuild(): array {
    $themes = $this->readScssPalettes();
    $this->cache->set(static::CACHE_NAME, $themes, time() + static::CACHE_MAX_AGE);
    return $themes;
  }

}
