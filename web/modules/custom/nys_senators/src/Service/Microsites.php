<?php

namespace Drupal\nys_senators\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides access to the microsite themes defined in CSS.
 */
class Microsites {

  /**
   * The cache key for the compiled theme information.
   */
  const CACHE_NAME_MICROSITES = 'nys_senators.microsite_urls';

  /**
   * The cache key for the compiled theme information.
   */
  const CACHE_NAME_THEMES = 'nys_senators.microsite_themes';

  /**
   * The maximum age in seconds of the theme compilation.
   */
  const CACHE_MAX_AGE_THEMES = 86400;

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
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Symphony's Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CacheBackendInterface $cache,
    ThemeHandlerInterface $theme,
    RequestStack $requestStack,
  ) {
    $this->cache = $cache;
    $this->theme = $theme;
    $this->entityTypeManager = $entityTypeManager;
    $this->request = $requestStack->getCurrentRequest();
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
    $cache = $this->cache->get(static::CACHE_NAME_THEMES);
    $themes = $cache ? ($cache->data ?? []) : [];
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
    $this->cache->set(static::CACHE_NAME_THEMES, $themes, time() + static::CACHE_MAX_AGE_THEMES);
    return $themes;
  }

  /**
   * Gets the URL to a senator's microsite.  Empty string, if not found.
   */
  public function getMicrosite(Term $senator): string {
    return $this->getMicrosites()[$senator->id()] ?? '';
  }

  /**
   * Gets an array matching senator term ID to senator microsite URL.
   */
  public function getMicrosites(): array {
    $sites = $this->cache->get(static::CACHE_NAME_MICROSITES);
    $ret = $sites ? $sites->data : [];
    if (!$ret) {
      $ret = $this->compileMicrosites();
    }
    return $ret;
  }

  /**
   * Compiles an array, such that [<senator_id> => <landing_url>, ...].
   *
   * All senators with landing pages are included, regardless of "active"
   * status.  If the page type term cannot be found, or if microsite pages
   * can not be loaded, an empty array will be returned.
   */
  protected function compileMicrosites(): array {
    // Get the term ID for landing pages.
    try {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties(
                [
                  'vid' => 'microsite_page_type',
                  'name' => 'Landing',
                ]
            );
      $landing_term = current($terms);
      $landing_id = $landing_term->id();
    }
    catch (\Throwable) {
      $landing_id = 0;
    }

    // Find all microsite_page nodes assigned to the "Landing" page type.
    try {
      $pages = $this->entityTypeManager
        ->getStorage('node')
        ->loadByProperties(
                [
                  'type' => 'microsite_page',
                  'field_microsite_page_type' => $landing_id,
                ]
            );
    }
    catch (\Throwable) {
      $pages = [];
    }

    // Compile the array, in the form [<senator_id> => <landing_page_url>, ...].
    $urls = [];
    foreach ($pages as $page) {
      try {
        $senator_id = $page->field_senator_multiref->entity->id() ?? 0;
        $url_options = [
          'absolute' => TRUE,
          'base_url' => $this->request->getSchemeAndHttpHost(),
        ];
        $url = $page->toUrl('canonical', $url_options)->toString();
      }
      catch (\Throwable) {
        $senator_id = 0;
        $url = '';
      }
      if ($senator_id && $url) {
        $urls[$senator_id] = $url;
      }
    }

    return $urls;
  }

}
