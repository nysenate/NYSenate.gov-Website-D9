<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for an individual agenda.
 *
 * @OpenlegApiResponseNew(
 *   id = "agenda",
 *   label = @Translation("Agenda Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class Agenda extends ResponseItem {

  /**
   * The display-ready title of the agenda.
   */
  public function title(): string {
    $year = $this->result()->id->year ?? '';
    $num = $this->result()->id->number ?? '';
    return ($year && $num) ? "$year - $num" : '';
  }

}
