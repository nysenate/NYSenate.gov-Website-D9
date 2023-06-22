<?php

namespace Drupal\nys_legislation_explorer;

/**
 * Helper/service methods relevant to user registration.
 */
class SearchAdvancedLegislationHelper {

  /**
   * Checks to see if params are set in the url indicating a results page.
   */
  public function isResultsPage() {
    $results_page = FALSE;
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if ($request->query->get('type')
          || $request->query->get('session_year')
          || $request->query->get('issue')
          || $request->query->get('printno')
          || $request->query->get('status')
          || $request->query->get('sponsor')
          || $request->query->get('full_text')
          || $request->query->get('committee')
          || $request->query->get('transcript_type')
          || $request->query->get('meeting_date')
          || $request->query->get('publish_date')
          || $request->query->get('date')
      ) {
      return $results_page = TRUE;
    }
  }

}
