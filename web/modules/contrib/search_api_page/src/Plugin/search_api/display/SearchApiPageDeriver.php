<?php

namespace Drupal\search_api_page\Plugin\search_api\display;

use Drupal\search_api\Display\DisplayDeriverBase;
use Drupal\search_api_page\Entity\SearchApiPage;

/**
 * Derives a display plugin definition for all pages.
 */
class SearchApiPageDeriver extends DisplayDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (isset($this->derivatives)) {
      return $this->derivatives;
    }
    $this->derivatives = [];

    $pages = SearchApiPage::loadMultiple();
    if (empty($pages)) {
      return $this->derivatives;
    }

    $this->derivatives = $this->getDerivatives($pages, $base_plugin_definition);

    return $this->derivatives;
  }

  /**
   * Creates derived plugin definitions for pages.
   *
   * @param \Drupal\search_api_page\SearchApiPageInterface[] $pages
   *   The pages to create plugins for.
   * @param array $base_plugin_definition
   *   The plugin definition for this plugin.
   *
   * @return array
   *   Returns an array of plugin definitions, keyed by derivative ID.
   */
  protected function getDerivatives(array $pages, array $base_plugin_definition) {
    $plugin_derivatives = [];

    foreach ($pages as $page) {
      $label = $page->label();
      $description = $this->t('The %label search page.', ['%label' => $label]);
      $plugin_derivatives[$page->id()] = [
        'label' => $label,
        'description' => $description,
        'index' => $page->getIndex(),
        'path' => '/' . $page->getPath(),
      ] + $base_plugin_definition;
    }

    return $plugin_derivatives;
  }

}
