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

  /**
   * Returns an array of all identifiers in this update's list.
   *
   * As a generic class, ResponseUpdate cannot do anything useful here.  This
   * method should be overridden by each child type.
   */
  public function listIds(): array {
    return [];
  }

}
