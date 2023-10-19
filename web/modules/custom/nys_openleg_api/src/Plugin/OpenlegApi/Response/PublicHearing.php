<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for an individual public hearing transcript.
 *
 * @OpenlegApiResponseNew(
 *   id = "hearing",
 *   label = @Translation("Public Hearing Transcript Item"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class PublicHearing extends ResponseItem {

  /**
   * Getter alias for the transcript text.
   */
  public function text():string {
    return $this->response->result->text ?? '';
  }

}
