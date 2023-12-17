<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;

/**
 * Openleg Import plugin for calendars.
 *
 * @OpenlegImporter(
 *   id = "calendars",
 *   label = @Translation("Calendars"),
 *   description = @Translation("Import plugin for calendars."),
 *   requester = "calendar"
 * )
 */
class Calendars extends ImporterBase {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    $year = $item->year ?? '';
    $num = $item->calendarNumber ?? '';
    return ($year && $num) ? "$year/$num" : '';
  }

  /**
   * Imports all items for a single year.
   *
   * Note that this is a calendar year, not a legislative session year.
   */
  public function importYear(string $year): ImportResult {
    /**
     * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\CalendarSimpleList $items
     */
    $items = $this->requester->retrieve((int) $year, ['limit' => 0]);
    return $this->import($this->getIdFromYearList($items));
  }

}
