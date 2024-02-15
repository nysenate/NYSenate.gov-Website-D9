<?php

namespace Drupal\turnstile\Turnstile;

/**
 * Adds the appropriate RequestMethodInterface.
 */
interface RequestMethodInterface {

  /**
   * Submit the request with the specified parameters.
   *
   * @param string $url
   *   The URL.
   * @param array $params
   *   Request parameters.
   *
   * @return object
   *   Body of the Turnstile response
   */
  public function submit($url, array $params);

}
