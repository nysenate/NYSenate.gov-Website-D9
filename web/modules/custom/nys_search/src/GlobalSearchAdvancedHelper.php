<?php

namespace Drupal\nys_search;

/**
 * Helper/service methods relevant to global search.
 */
class GlobalSearchAdvancedHelper {

  /**
   * Checks to see if params are set in the url indicating a results page.
   */
  public function isResultsPage() {
    $results_page = FALSE;
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if ($request->query->get('full_text')) {
      $results_page = TRUE;
    }

    return $results_page;
  }

}
