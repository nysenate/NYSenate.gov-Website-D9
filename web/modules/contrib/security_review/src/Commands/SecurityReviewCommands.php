<?php

namespace Drupal\security_review\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\security_review\Checklist;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityReview;
use Drush\Commands\DrushCommands;

/**
 * Class SecurityReviewCommands.
 *
 * @package Drupal\security_review\Commands
 */
class SecurityReviewCommands extends DrushCommands {

  /**
   * Security review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReviewService;

  /**
   * Checklist service.
   *
   * @var \Drupal\security_review\Checklist
   */
  protected $checklistService;

  /**
   * Constructs a SecurityReviewCommands object.
   *
   * @param \Drupal\security_review\SecurityReview $security_review
   *   Security review service.
   * @param \Drupal\security_review\Checklist $checklist
   *   Checklist service.
   */
  public function __construct(SecurityReview $security_review, Checklist $checklist) {
    $this->securityReviewService = $security_review;
    $this->checklistService = $checklist;
  }

  /**
   * Run the Security Review checklist.
   *
   * @command security:review
   * @option store
   *   Write results to the database
   * @option log
   *   Log results of each check to watchdog, defaults to off
   * @option lastrun
   *   Do not run the checklist, just print last results
   * @option check
   *   Comma-separated list of specified checks to run. See README.txt for list of options
   * @option skip
   *   Comma-separated list of specified checks not to run. This takes precedence over --check
   * @option short
   *   Short result messages instead of full description (e.g. 'Text formats')
   * @option results
   *   Show the incorrect settings for failed checks.
   * @usage secrev
   *   Run the checklist and output the results
   * @usage secrev --store
   *   Run the checklist, store, and output the results
   * @usage secrev --lastrun
   *   Output the stored results from the last run of the checklist
   * @aliases secrev, security-review
   * @format table
   * @pipe-format csv
   * @fields-default message, status
   * @field-labels
   *   message: Message
   *   status: Status
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Row of results.
   */
  public function securityReview(
    $options = [
      'store' => FALSE,
      'log' => FALSE,
      'lastrun' => FALSE,
      'check' => NULL,
      'skip' => NULL,
      'short' => FALSE,
      'results' => FALSE,
    ]
  ) {
    $store = $options['store'];
    $log = $options['log'];
    $last_run = $options['lastrun'];
    $run_checks = $options['check'];
    $skip_checks = $options['skip'];
    $short_titles = $options['short'];
    $show_findings = $options['results'];

    // Set temporary logging.
    $log = in_array($log, [TRUE, 1, 'TRUE']);
    $this->securityReviewService->setLogging($log, TRUE);

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
        foreach (explode(',', $run_checks) as $check) {
          $checks[] = $this->getCheck($check);
        }
      }
      else {
        // Get the whole checklist.
        $checks = $this->checklistService->getChecks();
      }

      // Mark checks listed after --skip for removal.
      if (!empty($skip_checks)) {
        foreach (explode(',', $skip_checks) as $skip_check) {
          $to_skip[] = $this->getCheck($skip_check);
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
        throw new \Exception(dt("No checks to run. Run 'drush help secrev' for option use or consult the drush section of API.txt for further help."));
      }

      // Run the checks.
      $results = $this->checklistService->runChecks($checks, TRUE);

      // Store the results.
      if ($store) {
        $this->checklistService->storeResults($results);
      }
    }
    else {
      // Show the latest stored results.
      foreach ($this->checklistService->getChecks() as $check) {
        $last_result = $check->lastResult($show_findings);
        if ($last_result instanceof CheckResult) {
          $results[] = $last_result;
        }
      }
    }

    return new RowsOfFields($this->formatResults($results, $short_titles, $show_findings));
  }

  /**
   * Helper function to compile Security Review results.
   *
   * @param \Drupal\security_review\CheckResult[] $results
   *   An array of CheckResults.
   * @param bool $short_titles
   *   Whether to use short message (check title) or full check success or
   *   failure message.
   * @param bool $show_findings
   *   Whether to print failed check results.
   *
   * @return array
   *   The results of the security review checks.
   */
  private function formatResults(array $results, $short_titles = FALSE, $show_findings = FALSE) {
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

  /**
   * Helper function for parsing input check name strings.
   *
   * @param string $check_name
   *   The check to get.
   *
   * @return \Drupal\security_review\Check|null
   *   The found Check.
   */
  private function getCheck($check_name) {
    // Default namespace is Security Review.
    $namespace = 'security_review';
    $title = $check_name;

    // Set namespace and title if explicitly defined.
    if (strpos($check_name, ':') !== FALSE) {
      list($namespace, $title) = explode(':', $check_name);
    }

    // Return the found check if any.
    return $this->checklistService->getCheck($namespace, $title);
  }

}
