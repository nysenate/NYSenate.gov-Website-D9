<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Openleg API Response plugin for an individual floor transcript.
 *
 * @OpenlegApiResponse(
 *   id = "transcript",
 *   label = @Translation("Floor Transcript Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class FloorTranscript extends ResponsePluginBase {

  /**
   * Getter alias for the transcript text.
   */
  public function text():string {
    return $this->response->result->text ?? '';
  }

}
