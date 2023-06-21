<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg\BillHelper;

/**
 * Openleg API Response plugin for a list of calendars in a calendar year.
 *
 * @OpenlegApiResponse(
 *   id = "calendar-simple list",
 *   label = @Translation("Calendar Year List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class CalendarSimpleList extends ResponseSearch {

  /**
   * Gets the request names for a list of bill references.
   *
   * @param array $items
   *   An array of bill references, such as in the 'entries' properties.
   *
   * @return array
   *   The list of bill print numbers.
   */
  public function getBillIdsFromList(array $items): array {
    return array_filter(
          array_unique(
              array_map(
                  function ($v) {
                        return BillHelper::formatTitle($v, TRUE, '/');
                  },
                  $items
              )
          )
      );
  }

}
