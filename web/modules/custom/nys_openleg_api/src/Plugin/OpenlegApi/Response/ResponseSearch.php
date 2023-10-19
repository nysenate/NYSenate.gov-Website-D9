<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Represents a search return from Openleg.
 *
 * @OpenlegApiResponseNew(
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
    return $this->response->total ?? 0;
  }

  /**
   * Gets the offset information from the response.
   */
  public function offset(): array {
    return [
      'start' => $this->response->offsetStart ?? 1,
      'end' => $this->response->offsetEnd ?? 1,
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
