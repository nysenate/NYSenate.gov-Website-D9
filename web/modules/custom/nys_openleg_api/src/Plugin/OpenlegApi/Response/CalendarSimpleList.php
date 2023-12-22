<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of calendars in a calendar year.
 *
 * @OpenlegApiResponse(
 *   id = "calendar-simple list",
 *   label = @Translation("Calendar Year List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class CalendarSimpleList extends YearBasedSearchList {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    $year = $item->year ?? '';
    $num = $item->calendarNumber ?? '';
    return ($year && $num) ? "$year/$num" : '';
  }

  /**
   * Formatter for calendar titles.
   *
   * It sucks this has to be here, but the alternative is a circular dependency
   * with nys_openleg.  This mirrors nys_openleg\BillHelper::formatTitle().
   *
   * @param object $item
   *   Ostensibly an OpenLeg representation of a bill object.  Can be any object
   *   with 'session', and 'basePrintNo' properties.
   *
   * @return string
   *   The title, in the form "<session>/<base_print_number>".
   */
  protected static function formatTitle(object $item): string {
    $num = $item->basePrintNo ?? '';
    $session = $item->session ?? '';
    return ($session && $num) ? $item->session . '/' . $num : '';
  }

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
    return array_filter(array_unique(
      array_map(
        function ($v) {
          return self::formatTitle($v);
        },
        $items
      )
    ));
  }

}
