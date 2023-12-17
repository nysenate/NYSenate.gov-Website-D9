<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Openleg API Response plugin for an individual agenda.
 *
 * @OpenlegApiResponse(
 *   id = "agenda",
 *   label = @Translation("Agenda Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class Agenda extends ResponsePluginBase {

  /**
   * The display-ready title of the agenda.
   */
  public function title(): string {
    $year = $this->result()->id->year ?? '';
    $num = $this->result()->id->number ?? '';
    return ($year && $num) ? "$year - $num" : '';
  }

}
