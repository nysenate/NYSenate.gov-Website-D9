<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

use Drupal\nys_openleg_api\ResponsePluginBase;

/**
 * Openleg API Response plugin for a law tree, part of Statute items.
 *
 * @OpenlegApiResponse(
 *   id = "law-tree",
 *   label = @Translation("Statute Tree"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class StatuteTree extends ResponsePluginBase {

  /**
   * Gets the array of child objects from the most recent call.
   *
   * @return array
   *   Array of child objects.
   */
  public function children(): array {
    return $this->response->result->documents->documents->items ?? [];
  }

  /**
   * Gets the array of documents for this location.
   *
   * @return object
   *   The documents object of the response.
   */
  public function documents(): object {
    return $this->response->result->documents ?? ((object) []);
  }

  /**
   * Gets a sorted list of history markers for the most recent call.
   *
   * @return array
   *   An array of available history markers.
   */
  public function publishDates(): array {
    $sorted = $this->response->result->publishedDates ?? [];
    sort($sorted);
    return $sorted;
  }

  /**
   * Gets the location ID of this tree's root.
   *
   * @return string
   *   The location ID, or a blank string if not populated.
   */
  public function location(): string {
    return $this->documents()->locationId ?? '';
  }

  /**
   * Gets the publish date of the current document.
   *
   * Note that a doc tree can hold pointers to several levels of documents.
   * This method returns the top level only.
   *
   * @return string
   *   The publish date as Y-m-d, or an empty string on failure.
   */
  public function getActiveDate(): string {
    return $this->success()
      ? ($this->result->documents->activeDate ?? '')
      : '';
  }

}
