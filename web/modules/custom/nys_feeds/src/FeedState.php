<?php

namespace Drupal\nys_feeds;

use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\Request;

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

  /**
   * A warehouse for values calculated within the plugin.
   *
   * @var array
   */
  public array $vars = [];

  /**
   * A cache dependency object to be populated by the feed.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected CacheableMetadata $cache;

  /**
   * Indicates if this request should use a cacheable response.
   *
   * @var bool
   */
  protected bool $useCache = TRUE;

  /**
   * The original request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  public function __construct(Request $request, int $code = 200, array $messages = [], array $data = []) {
    $this->init($request, $code, $messages, $data);
  }

  /**
   * Reset the state.
   */
  public function init(Request $request, int $code = 200, array $messages = [], array $data = []): static {
    // Passed properties.
    $this->request = $request;
    $this->code = $code;
    $this->messages = $messages;
    $this->data = $data;

    // Calculations and defaults.
    $this->params = $request->query->all();
    $this->vars = [];
    $this->cache(TRUE);

    return $this;
  }

  /**
   * Sets the cache status for this request.  Chainable.
   */
  public function setCaching(bool $enable = TRUE): static {
    $this->useCache = $enable;
    return $this;
  }

  /**
   * Signals if the cache is enabled or not.
   */
  public function useCache(): bool {
    return $this->useCache;
  }

  /**
   * Accessor for the cache metadata.
   */
  public function cache(bool $reset = FALSE): CacheableMetadata {
    if ($reset) {
      $this->cache = new CacheableMetadata();
    }
    return $this->cache;
  }

  /**
   * Accessor for the request.
   */
  public function request(): Request {
    return $this->request;
  }

}
