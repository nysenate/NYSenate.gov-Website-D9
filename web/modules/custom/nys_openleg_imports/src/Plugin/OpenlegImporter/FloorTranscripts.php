<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImporter;

use Drupal\nys_openleg_imports\ImporterBase;
use Drupal\nys_openleg_imports\ImportResult;

/**
 * Openleg Import plugin for floor transcripts.
 *
 * @OpenlegImporter(
 *   id = "floor_transcripts",
 *   label = @Translation("Floor Transcripts"),
 *   description = @Translation("Import plugin for floor transcripts."),
 *   requester = "floor_transcript"
 * )
 */
class FloorTranscripts extends ImporterBase {

  /**
   * {@inheritDoc}
   */
  public function id(object $item): string {
    return $item->dateTime ?? '';
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
