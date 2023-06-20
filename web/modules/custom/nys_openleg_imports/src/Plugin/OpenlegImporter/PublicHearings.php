<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;
use Drupal\nys_openleg_imports\ImportResult;

/**
 * Openleg Import plugin for public hearing transcripts.
 *
 * @OpenlegImporter(
 *   id = "public_hearings",
 *   label = @Translation("Public Hearing Transcripts"),
 *   description = @Translation("Import plugin for public hearing transcripts."),
 *   requester = "hearing_transcript"
 * )
 */
class PublicHearings extends ImporterBase {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    return $item->result->id ?? '';
  }

  /**
   * Imports all items for a single year.
   *
   * Note that this is a calendar year, not a legislative session year.
   */
  public function importYear(string $year): ImportResult {
    /**
     * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\TranscriptYearList $items
*/
    $items = $this->requester->retrieve((int) $year);
    return $this->import($this->getIdFromYearList($items));
  }

}
