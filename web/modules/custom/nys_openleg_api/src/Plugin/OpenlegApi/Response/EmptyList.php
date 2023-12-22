<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Represents an empty list response from Openleg.
 *
 * @OpenlegApiResponse(
 *   id = "empty list",
 *   label = @Translation("Empty List Response"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class EmptyList extends ResponseUpdate {

  /**
   * {@inheritDoc}
   *
   * Return an empty array.
   */
  public function listIds(): array {
    return [];
  }

}
