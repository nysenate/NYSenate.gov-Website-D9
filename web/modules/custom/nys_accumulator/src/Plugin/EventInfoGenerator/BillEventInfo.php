<?php

namespace Drupal\nys_accumulator\Plugin\EventInfoGenerator;

use Drupal\nys_accumulator\EventInfoGeneratorBase;

/**
 * Generates the event info for bill-related accumulator events.
 *
 * @EventInfoGenerator(
 *   id = "bill",
 *   requires = { "node:bill", "node:resolution" },
 *   content_url = "/node",
 *   fields = {
 *     "bill_number" = "field_ol_base_print_no",
 *     "bill_year" = "field_ol_session",
 *     "sponsors" = "field_ol_sponsor_name",
 *   }
 * )
 */
class BillEventInfo extends EventInfoGeneratorBase {

}
