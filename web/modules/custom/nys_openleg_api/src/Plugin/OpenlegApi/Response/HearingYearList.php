<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of public hearings in a calendar year.
 *
 * @OpenlegApiResponse(
 *   id = "hearing-id list",
 *   label = @Translation("Public Hearing Year List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class HearingYearList extends YearBasedSearchList {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    return $item->id ?? '';
  }

}
