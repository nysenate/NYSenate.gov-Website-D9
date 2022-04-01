<?php

/**
 * @file
 * Drush commands for Security Review module.
 */

use Drupal\security_review\CheckResult;

/**
 * Implements hook_drush_command().
 */
function security_review_drush_command() {
  $items = [];

  $items['security-review'] = [
    'aliases' => ['secrev'],
    'callback' => 'security_review_drush',
    'description' => "Run the Security Review checklist",
    'options' => [
      'store' => 'Write results to the database',
      'log' => 'Log results of each check to watchdog, defaults to off',
      'lastrun' => 'Do not run the checklist, just print last results',
      'check' => 'Comma-separated list of specified checks to run. See README.txt for list of options',
      'skip' => 'Comma-separated list of specified checks not to run. This takes precedence over --check.',
      'short' => "Short result messages instead of full description (e.g. 'Text formats')",
      'results' => 'Show the incorrect settings for failed checks',
    ],
    'examples' => [
      'secrev' => 'Run the checklist and output the results',
      'secrev --store' => 'Run the checklist, store, and output the results',
      'secrev --lastrun' => 'Output the stored results from the last run of the checklist',
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'csv',
      'fields-default' => ['message', 'status'],
      'field-labels' => [
        'message' => 'Message',
        'status' => 'Status',
        'findings' => 'Findings',
      ],
      'output-data-type' => 'format-table',
    ],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function security_review_drush_help($section) {
  switch ($section) {
    case 'drush:security-review':
      return dt("Run configuration security checks on your Drupal site.");
  }
}

/**
 * Runs a checklist and displays results.
 */
function security_review_drush() {
  /** @var \Drupal\security_review\SecurityReview $security_review */
  $security_review = Drupal::service('security_review');

  /** @var \Drupal\security_review\Checklist $checklist */
  $checklist = Drupal::service('security_review.checklist');

  $store = drush_get_option('store');
  $log = drush_get_option('log');
  $last_run = drush_get_option('lastrun');
  $run_checks = drush_get_option_list('check');
  $skip_checks = drush_get_option_list('skip');
  $short_titles = drush_get_option('short');
  $show_findings = drush_get_option('results');

  // Set temporary logging.
  $log = in_array($log, [TRUE, 1, 'TRUE']);
  $security_review->setLogging($log, TRUE);

  if (!empty($short_titles)) {
    $short_titles = TRUE;
  }
  else {
    $short_titles = FALSE;
  }

  $results = [];
  if (!$last_run) {
    // Do a normal security review run.
    /** @var \Drupal\security_review\Check[] $checks */
    $checks = [];
    /** @var \Drupal\security_review\Check[] $to_skip */
    $to_skip = [];

    // Fill the $checks array.
    if (!empty($run_checks)) {
      // Get explicitly specified checks.
      foreach ($run_checks as $check) {
        $checks[] = _security_review_drush_get_check($check);
      }
    }
    else {
      // Get the whole checklist.
      $checks = $checklist->getChecks();
    }

    // Mark checks listed after --skip for removal.
    if (!empty($skip_checks)) {
      foreach ($skip_checks as $skip_check) {
        $to_skip[] = _security_review_drush_get_check($skip_check);
      }
    }

    // If storing, mark skipped checks for removal.
    if ($store) {
      foreach ($checks as $check) {
        if ($check->isSkipped()) {
          $to_skip[] = $check;
        }
      }
    }

    // Remove the skipped checks from $checks.
    foreach ($to_skip as $skip_check) {
      foreach ($checks as $key => $check) {
        if ($check->id() == $skip_check->id()) {
          unset($checks[$key]);
        }
      }
    }

    // If $checks is empty at this point, return with an error.
    if (empty($checks)) {
      return drush_set_error('EMPTY_CHECKLIST', dt("No checks to run. Run 'drush help secrev' for option use or consult the drush section of API.txt for further help."));
    }

    // Run the checks.
    $results = $checklist->runChecks($checks, TRUE);

    // Store the results.
    if ($store) {
      $checklist->storeResults($results);
    }
  }
  else {
    // Show the latest stored results.
    foreach ($checklist->getChecks() as $check) {
      $last_result = $check->lastResult($show_findings);
      if ($last_result instanceof CheckResult) {
        $results[] = $last_result;
      }
    }
  }

  return _security_review_drush_format_results($results, $short_titles, $show_findings);
}

/**
 * Helper function for parsing input check name strings.
 *
 * @param string $check_name
 *   The check to get.
 *
 * @return \Drupal\security_review\Check|null
 *   The found Check.
 */
function _security_review_drush_get_check($check_name) {
  /** @var \Drupal\security_review\Checklist $checklist */
  $checklist = Drupal::service('security_review.checklist');

  // Default namespace is Security Review.
  $namespace = 'security_review';
  $title = $check_name;

  // Set namespace and title if explicitly defined.
  if (strpos($check_name, ':') !== FALSE) {
    list($namespace, $title) = explode(':', $check_name);
  }

  // Return the found check if any.
  return $checklist->getCheck($namespace, $title);
}

/**
 * Helper function to compile Security Review results.
 *
 * @param \Drupal\security_review\CheckResult[] $results
 *   An array of CheckResults.
 * @param bool $short_titles
 *   Whether to use short message (check title) or full check success or failure
 *   message.
 * @param bool $show_findings
 *   Whether to print failed check results.
 *
 * @return array
 *   The results of the security review checks.
 */
function _security_review_drush_format_results(array $results, $short_titles = FALSE, $show_findings = FALSE) {
  $output = [];

  foreach ($results as $result) {
    if ($result instanceof CheckResult) {
      if (!$result->isVisible()) {
        // Continue with the next check.
        continue;
      }

      $check = $result->check();
      $message = $short_titles ? $check->getTitle() : $result->resultMessage();
      $status = 'notice';

      // Set log level according to check result.
      switch ($result->result()) {
        case CheckResult::SUCCESS:
          $status = 'success';
          break;

        case CheckResult::FAIL:
          $status = 'failed';
          break;

        case CheckResult::WARN:
          $status = 'warning';
          break;

        case CheckResult::INFO:
          $status = 'info';
          break;
      }

      // Attach findings.
      if ($show_findings) {
        $findings = trim($result->check()->evaluatePlain($result));
        if ($findings != '') {
          $message .= "\n" . $findings;
        }
      }

      $output[$check->id()] = [
        'message' => (string) $message,
        'status' => $status,
        'findings' => $result->findings(),
      ];
    }
  }

  return $output;
}
