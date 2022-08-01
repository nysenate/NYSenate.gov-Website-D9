<?php

namespace Drupal\nys_openleg\Api;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for Openleg API Request plugins.
 */
interface RequestPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * Retrieve an individual object from Openleg.
   *
   * @param string $name
   *   The resource name to retrieve.
   * @param array $params
   *   Query string parameters to be added.
   *
   * @return \Drupal\nys_openleg\Api\ResponsePluginBase
   *   The response object.
   */
  public function retrieve(string $name, array $params = []): ResponsePluginBase;

  /**
   * Hook for implementers to massage parameters before the request.
   *
   * @param array $params
   *   Optional key-value parameters provided by the calling code.  These
   *   should take precedence where possible.
   *
   * @return array
   *   The prepared parameters, ready for the request execution.
   */
  public function prepParams(array $params = []): array;

  /**
   * Retrieves a list of update blocks.
   *
   * @param mixed $time_from
   *   An epoch timestamp, or any string appropriate for strtotime()
   * @param mixed $time_to
   *   An epoch timestamp, or any string appropriate for strtotime()
   * @param array $params
   *   Query string parameters for the API request.
   *
   * @return \Drupal\nys_openleg\Api\ResponsePluginInterface
   *   The Response object.
   */
  public function retrieveUpdates($time_from, $time_to, array $params = []);

  /**
   * Retrieves a search result from Openleg.
   *
   * @param string $search_term
   *   The search term.
   * @param array $params
   *   Query string parameters for the API request.
   *
   * @return \Drupal\nys_openleg\Api\ResponsePluginInterface
   *   The response object.
   */
  public function retrieveSearch(string $search_term, array $params = []);

  /**
   * Sets default parameters for all calls from this requester.
   */
  public function setParams(array $params);

}
