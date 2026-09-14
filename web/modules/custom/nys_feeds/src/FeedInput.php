<?php

namespace Drupal\nys_feeds;

use Symfony\Component\HttpFoundation\Request;

/**
 * Organizes inputs required by NysFeed plugins.
 */
class FeedInput {

  /**
   * Holds the request's query string parameters.
   *
   * @var array
   */
  protected array $queryString = [];

  /**
   * A repository for plugins to store and manipulate run-time data.
   *
   * @var array
   */
  public array $vars = [];

  /**
   * Constructor.
   */
  public function __construct(protected Request $request, protected array $config = []) {
    $this->queryString = array_filter($request->query->all());
  }

  /**
   * Returns all or part of the query string.
   *
   * @param string|null $name
   *   An optional parameter name.
   * @param mixed|null $default
   *   The default value if the name does not exist.
   *
   * @return mixed
   *   If no $name is provided, the full query string array.  Otherwise, the
   *   query string named value, or the $default if $name does not exist.
   */
  public function getQueryString(?string $name = NULL, mixed $default = NULL): mixed {
    return is_null($name)
      ? $this->queryString
      : ($this->queryString[$name] ?? $default);
  }

  /**
   * Accessor for misc. config.
   */
  public function getConfig(): array {
    return $this->config;
  }

  /**
   * Accessor for the request.
   */
  public function request(): Request {
    return $this->request;
  }

}
