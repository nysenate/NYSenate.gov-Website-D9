<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for an individual floor transcript.
 *
 * @OpenlegApiResponse(
 *   id = "transcript",
 *   label = @Translation("Floor Transcript Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class FloorTranscript extends ResponseItem {

  /**
   * Getter alias for the transcript text.
   */
  public function text():string {
    return $this->response->result->text ?? '';
  }

}
