<?php

namespace Drupal\nys_feeds;

/**
 * Maintains a running state of the process of generating a feed response.
 */
class FeedState {

  /**
   * The status code to be used for the response.
   *
   * @var int
   */
  public int $code = 200;

  /**
   * A list of messages to include in the response.
   *
   * @var array
   */
  public array $messages = [];

  /**
   * The feed payload.
   *
   * @var array
   */
  public array $data = [];

  /**
   * The feed parameters (e.g., query string key/value pairs).
   *
   * @var array
   */
  public array $params = [];

  public function __construct(array $params = [], int $code = 200, array $messages = [], array $data = []) {
    $this->init($params, $code, $messages, $data);
  }

  /**
   * Reset the state.
   */
  public function init(array $params = [], int $code = 200, array $messages = [], array $data = []): static {
    $this->params = $params;
    $this->code = $code;
    $this->messages = $messages;
    $this->data = $data;
    return $this;
  }

}
