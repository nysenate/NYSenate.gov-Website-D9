<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg\Api\ResponsePluginBase;

/**
 * Represents a list of update blocks from Openleg.
 *
 * @OpenlegApiResponse(
 *   id = "response_update",
 *   label = @Translation("Generic Updates Response"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class ResponseUpdate extends ResponsePluginBase {

  /**
   * Accessor for the array of update blocks.
   */
  public function items(): array {
    return $this->result()->items ?? [];
  }

}
