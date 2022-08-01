<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg\Api\ResponsePluginBase;

/**
 * Represents a search return from Openleg.
 *
 * @OpenlegApiResponse(
 *   id = "search-results list",
 *   label = @Translation("Generic Search Response"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class ResponseSearch extends ResponsePluginBase {

  /**
   * Gets the response's "total" data point.
   */
  public function total(): int {
    return $this->result()->total ?? 0;
  }

  /**
   * Gets the offset information from the response.
   */
  public function offset(): array {
    return [
      'start' => $this->result()->offsetStart ?? 1,
      'end' => $this->result()->offsetEnd ?? 1,
      'total' => $this->total(),
    ];
  }

  /**
   * Gets the limit parameter used in the response.
   */
  public function limit(): int {
    return $this->result()->limit ?? 0;
  }

  /**
   * Gets a count of the response's items.
   */
  public function count(): int {
    return count($this->items());
  }

  /**
   * Returns an array of the search result items.
   */
  public function items(): array {
    return $this->result()->items ?? [];
  }

}
