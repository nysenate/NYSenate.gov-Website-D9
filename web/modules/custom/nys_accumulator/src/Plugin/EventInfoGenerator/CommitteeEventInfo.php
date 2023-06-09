<?php

namespace Drupal\nys_accumulator\Plugin\EventInfoGenerator;

use Drupal\nys_accumulator\EventInfoGeneratorBase;

/**
 * Generates the event info for committee-related accumulator events.
 *
 * @EventInfoGenerator(
 *   id = "committee",
 *   requires = { "taxonomy_term:committees" },
 *   content_url = "/taxonomy/term",
 *   fields = {
 *     "committee_name" = "name",
 *   }
 * )
 */
class CommitteeEventInfo extends EventInfoGeneratorBase {

}
