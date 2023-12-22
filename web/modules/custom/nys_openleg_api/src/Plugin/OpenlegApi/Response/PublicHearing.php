<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Openleg API Response plugin for an individual public hearing transcript.
 *
 * @OpenlegApiResponse(
 *   id = "hearing",
 *   label = @Translation("Public Hearing Transcript Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class PublicHearing extends ResponsePluginBase {

  /**
   * Getter alias for the transcript text.
   */
  public function text():string {
    return $this->response->result->text ?? '';
  }

}
