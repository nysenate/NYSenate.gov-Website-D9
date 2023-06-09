<?php

namespace Drupal\nys_accumulator\Plugin\EventInfoGenerator;

use Drupal\nys_accumulator\EventInfoGeneratorBase;

/**
 * Generates the event info for issue-related accumulator events.
 *
 * @EventInfoGenerator(
 *   id = "issue",
 *   requires = { "taxonomy_term:issues", "taxonomy_term:majority_issues" },
 *   content_url = "/taxonomy/term",
 *   fields = {
 *     "issue_name" = "name",
 *   }
 * )
 */
class IssueEventInfo extends EventInfoGeneratorBase {

}
