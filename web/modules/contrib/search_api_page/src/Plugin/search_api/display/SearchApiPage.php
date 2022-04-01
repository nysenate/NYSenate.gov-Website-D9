<?php

namespace Drupal\search_api_page\Plugin\search_api\display;

use Drupal\search_api\Display\DisplayPluginBase;

/**
 * Represents a Search API Pages search display.
 *
 * @SearchApiDisplay(
 *   id = "search_api_page",
 *   deriver = "Drupal\search_api_page\Plugin\search_api\display\SearchApiPageDeriver"
 * )
 */
class SearchApiPage extends DisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    if ($path = $this->getPath()) {
      $current_path = $this->getCurrentPath()->getPath();
      return (strpos($current_path, $path) !== FALSE);
    }
    return FALSE;
  }

}
