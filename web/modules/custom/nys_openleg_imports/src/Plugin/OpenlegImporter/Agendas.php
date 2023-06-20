<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;
use Drupal\nys_openleg_imports\ImportResult;

/**
 * Openleg Import plugin for agendas.
 *
 * @OpenlegImporter(
 *   id = "agendas",
 *   label = @Translation("Agendas"),
 *   description = @Translation("Import plugin for agendas."),
 *   requester = "agenda"
 * )
 */
class Agendas extends ImporterBase {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    $year = $item->id->year ?? '';
    $num = $item->id->number ?? '';
    return ($year && $num) ? "$year/$num" : '';
  }

  /**
   * Imports all items for a single year.
   *
   * Note that this is a calendar year, not a legislative session year.
   */
  public function importYear(string $year): ImportResult {
    /**
* @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\AgendaYearList $items
*/
    $items = $this->requester->retrieve((int) $year, ['limit' => 0]);
    return $this->import($this->getIdFromYearList($items));
  }

}
