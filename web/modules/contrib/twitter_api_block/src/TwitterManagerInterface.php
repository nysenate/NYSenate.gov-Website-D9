<?php

namespace Drupal\twitter_api_block;

/**
 * Common methods for a Twitter communicator.
 */
interface TwitterManagerInterface {

  /**
   * Make a call to the Twitter API.
   *
   * @param string $method
   *   Either GET or POST.
   * @param string $endpoint
   *   The requested resource.
   * @param array $parameters
   *   (optional) An associative array with query parameters for this call.
   *
   * @see https://developer.twitter.com/en/docs/twitter-api
   * @see https://developer.twitter.com/en/docs/twitter-api/migrate/twitter-api-endpoint-map
   */
  public function call(string $method, string $endpoint, array $parameters = []);

}
