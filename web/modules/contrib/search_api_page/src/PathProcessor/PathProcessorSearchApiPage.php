<?php

namespace Drupal\search_api_page\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PathProcessorSearchApiPage.
 */
class PathProcessorSearchApiPage implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * PathProcessorSearchApiPage constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager, ConfigFactoryInterface $config) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    foreach ($this->getSearchApiPagePathsUsingCleanUrls() as $search_api_clean_url_path) {
      $regex = '~^' . $search_api_clean_url_path . '~';
      if (preg_match($regex, $path)) {
        $keys = str_replace($search_api_clean_url_path, '', $path);
        return $search_api_clean_url_path . rawurlencode($keys);
      }
    }
    return $path;
  }

  /**
   * Get Search API page path for clean urls.
   */
  protected function getSearchApiPagePathsUsingCleanUrls() {
    $paths = [];
    $is_multilingual = $this->languageManager->isMultilingual();
    $all_languages = $this->languageManager->getLanguages();

    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    foreach ($this->entityTypeManager->getStorage('search_api_page')
      ->loadMultiple() as $search_api_page) {
      // Default path.
      $default_path = $search_api_page->getPath();

      // Loop over all languages so we can get the translated path (if any).
      foreach ($all_languages as $language) {
        $path = '';

        // Check if we are multilingual or not.
        if ($is_multilingual) {
          $path = $this->languageManager
            ->getLanguageConfigOverride($language->getId(), 'search_api_page.search_api_page.' . $search_api_page->id())
            ->get('path');
        }
        if (empty($path)) {
          $path = $default_path;
        }

        // Use clean urls or not.
        if ($search_api_page->getCleanUrl()) {
          $path .= '/';
          $paths[] = '/' . $path;
        }
      }
    }

    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($request === NULL || $path === "/") {
      return $path;
    }

    if (strpos($request->get('_route', ''), 'search_api_page.') !== 0) {
      return $path;
    }

    // Skip processing of no 'Search API Page' routes.
    $url_object = \Drupal::service('path.validator')->getUrlIfValid($path);
    if ($url_object && strpos($url_object->getRouteName(), 'search_api_page.') !== 0) {
      return $path;
    }

    if (!isset($options['language']) || empty($options['language'])) {
      return $path;
    }

    $search_api_page_id = $request->get('search_api_page_name');
    $config_name = 'search_api_page.search_api_page.' . $search_api_page_id;
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($options['language']);
    $path = \Drupal::config($config_name)->get('path');
    $this->languageManager->setConfigOverrideLanguage($original_language);

    // Preserve keys when switching between languages.
    if ($request->get('keys')) {
      $path .= '/' . $request->get('keys');
    }

    if (strpos($path ?? '', '/') !== 0) {
      return '/' . $path;
    }

    return $path;
  }

}
