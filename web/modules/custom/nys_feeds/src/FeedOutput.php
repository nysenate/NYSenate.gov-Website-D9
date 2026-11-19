<?php

namespace Drupal\nys_feeds;

/**
 * Consolidates the various metadata for an NysFeed output.
 */
class FeedOutput {

  /**
   * Repository of messages for the feed.
   *
   * @var array
   */
  public array $messages = [];

  /**
   * The feed's source data.
   *
   * @var array
   */
  public array $data = [];

  /**
   * A plain-text indication of the result, e.g., "OK", "ERROR".
   *
   * @var string
   */
  public string $status = '';

  /**
   * The HTTP status code to use for the response.
   *
   * @var int
   */
  public int $statusCode = 200;

  /**
   * Returns the JSONable array.
   */
  public function asArray(): array {
    return [
      'data' => $this->data,
      'messages' => $this->messages,
      'status' => $this->status,
    ];
  }

}
