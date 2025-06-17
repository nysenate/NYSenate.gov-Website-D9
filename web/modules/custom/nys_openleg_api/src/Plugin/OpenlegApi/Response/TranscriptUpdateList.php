<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of transcript updates.
 *
 * @OpenlegApiResponse(
 *   id = "transcript-update-token list",
 *   label = @Translation("Transcript Update List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class TranscriptUpdateList extends ResponseUpdate {

  /**
   * {@inheritDoc}
   */
  public function listIds(): array {
    return array_unique(
      array_filter(
        array_map(
          function ($v) {
            return $v->transcriptId->dateTime ?? '';
          },
          $this->items()
        )
      )
    );
  }

}
