<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of transcripts in a calendar year.
 *
 * @OpenlegApiResponse(
 *   id = "transcript-id list",
 *   label = @Translation("Transcript Year List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class TranscriptYearList extends YearBasedSearchList {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    return $item->dateTime ?? '';
  }

}
