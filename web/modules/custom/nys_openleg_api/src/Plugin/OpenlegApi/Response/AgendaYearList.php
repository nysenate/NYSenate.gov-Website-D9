<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of agendas in a calendar year.
 *
 * @OpenlegApiResponse(
 *   id = "agenda-summary list",
 *   label = @Translation("Agenda Year List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class AgendaYearList extends YearBasedSearchList {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    $year = $item->id->year ?? '';
    $num = $item->id->number ?? '';
    return ($year && $num) ? "$year/$num" : '';
  }

}
