<?php

/**
 * @file
 * Hooks provided by the Search API Page module.
 */

/**
 * Alter the Search API results page.
 *
 * Modules may implement this hook to alter the search results page elements,
 * all properties from \Drupal\search_api\Query\ResultSet are available here.
 *
 * @param array $build
 *   An array containing all page elements.
 * @param \Drupal\search_api\Query\ResultSet $query_result
 *   Search API query result.
 * @param \Drupal\search_api_page\SearchApiPageInterface $search_api_page
 *   The Search API Page entity object.
 *
 * @see \Drupal\search_api_page\Controller\SearchApiPageController
 */
function hook_search_api_page_alter(&$build, $query_result, $search_api_page) {
  $search_title = \Drupal::translation()->translate(
    'There are @count Search results at @path.',
    [
      '@count' => $query_result->getResultCount(),
      '@path' => $search_api_page->getPath(),
    ]
  );

  $build['#search_title'] = [
    '#markup' => $search_title,
  ];
}
