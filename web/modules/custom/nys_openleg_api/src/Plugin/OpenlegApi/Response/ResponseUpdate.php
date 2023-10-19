<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Represents a list of update blocks from Openleg.
 *
 * This is a template, and does not match a known OpenLeg response type.
 *
 * @OpenlegApiResponse(
 *   id = "response_update",
 *   label = @Translation("Generic Updates Response Template"),
 *   description = @Translation("Openleg API Response plugin template")
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
