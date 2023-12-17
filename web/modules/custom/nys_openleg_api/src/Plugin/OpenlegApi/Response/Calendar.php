<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Openleg API Response plugin for an individual calendar.
 *
 * @OpenlegApiResponse(
 *   id = "calendar",
 *   label = @Translation("Calendar Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class Calendar extends ResponsePluginBase {

  /**
   * The display-ready title of the calendar.
   */
  public function title(): string {
    $year = $this->result()->year ?? '';
    $num = $this->result()->calendarNumber ?? '';
    return ($year && $num) ? "$year - $num" : '';
  }

}
