<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;
use Drupal\nys_openleg_imports\ImportResult;

/**
 * Openleg Import plugin for bills and resolutions.
 *
 * @OpenlegImporter(
 *   id = "bills",
 *   label = @Translation("Bills and Resolutions"),
 *   description = @Translation("Import plugin for bills and resolutions."),
 *   requester = "bill"
 * )
 */
class Bills extends ImporterBase {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    $session = $item->session ?? '';
    $print = $item->basePrintNo ?? '';
    return ($session && $print) ? "$session/$print" : '';
  }

  /**
   * Imports all items for a single year.
   *
   * Note that this is a legislative session year, not a calendar year.
   */
  public function importYear(string $year): ImportResult {
    /**
     * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\BillYearList $items
*/
    $items = $this->requester->retrieve((int) $year, ['limit' => 0]);
    return $this->import($this->getIdFromYearList($items));
  }

}
