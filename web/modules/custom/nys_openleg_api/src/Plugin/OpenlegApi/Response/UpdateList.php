<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response generic plugin for a list of updates.
 *
 * This plugin handles the response type "update-token list" when searching for
 * updates, which is used by bills, calendars, and agendas.  Each item's type
 * can be found in the contentType property.
 *
 * @OpenlegApiResponseNew(
 *   id = "update-token list",
 *   label = @Translation("Generic Update List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class UpdateList extends ResponseUpdate {

  /**
   * {@inheritDoc}
   */
  public function listIds(): array {
    return array_unique(array_filter(array_map(
      function ($v) {
        switch ($v->contentType) {
          // e.g., "2021/1".
          case 'AGENDA':
            $year = $v->id->year ?? '';
            $num = $v->id->number ?? '';
            $ret = ($year && $num) ? "$year/$num" : '';
            break;

          // e.g., "2021/1".
          case 'CALENDAR':
            $year = $v->id->year ?? '';
            $num = $v->id->calendarNumber ?? '';
            $ret = ($year && $num) ? "$year/$num" : '';
            break;

          // e.g., "2021/S123", with no amendment marker.
          case 'BILL':
            $year = $v->id->session ?? '';
            $num = $v->id->basePrintNo ?? '';
            $ret = ($year && $num) ? "$year/$num" : '';
            break;

          default:
            $ret = '';
        }
        return $ret;
      },
      $this->items()
    )));
  }

}
