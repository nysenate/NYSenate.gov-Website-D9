<?php

namespace Drupal\nys_openleg\Api;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\nys_openleg\Service\ApiResponseManager;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseGeneric;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseItem;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate;

/**
 * A base class for requesting an object from OpenLeg.
 *
 * This plugin's mechanics assumes usage of Response classes found in
 * nys_openleg\Plugin\OpenlegApi\Response.  New request plugins extending
 * this class should also generate responses extending that family of classes.
 */
abstract class RequestPluginBase implements RequestPluginInterface {

  /**
   * The API endpoint to use, from the annotated definition.
   *
   * @var string
   */
  protected string $endpoint;

  /**
   * The API response, decoded from JSON.
   *
   * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseGeneric|null
   */
  public ?ResponseGeneric $response;

  /**
   * Default parameters for all requests.
   *
   * @var array
   */
  protected array $params = [];

  /**
   * An Openleg API Request object.
   *
   * @var \Drupal\nys_openleg\Api\Request
   */
  protected Request $request;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * Openleg API Response Manager service.
   *
   * @var \Drupal\nys_openleg\Service\ApiResponseManager
   */
  protected ApiResponseManager $responseManager;

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Constructor.
   */
  public function __construct($definition, Request $request, ApiResponseManager $responseManager, LoggerChannel $logger) {
    $this->definition = $definition;
    $this->endpoint = $definition['endpoint'];
    $this->request = $request;
    $this->responseManager = $responseManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): RequestPluginBase {
    $options = $configuration['request_options'] ?? [];
    unset($configuration['request_options']);
    return new static(
      $plugin_definition,
      new Request($plugin_definition['endpoint'], $options),
      $container->get('manager.openleg_responses'),
      $container->get('logger.channel.openleg_api')
    );
  }

  /**
   * Translates the params property into a key-value array for the query string.
   *
   * @return array
   *   The key-value array of parameters.
   */
  public function prepParams(array $params = []): array {
    return $params + $this->params;
  }

  /**
   * {@inheritDoc}
   *
   * Best efforts are made to return a search response object, but downstream
   * failures may force a ResponseGeneric object instead.
   *
   *   A response object.
   */
  public function retrieve(string $name, $params = []): ResponseItem {
    $params = $this->prepParams($params);

    // @todo generate mock error class instead of blank object?
    $this->response = $this->generateResponse($this->request->get($name, $params));

    if (!($this->response instanceof ResponseItem)) {
      $this->logger->warning('Fell back to untyped response: @type', ['@type' => $this->definition['id']]);
    }
    $this->processResponse();

    return $this->response;
  }

  /**
   * {@inheritDoc}
   *
   * This implementation defaults to retrieving all updates ever if no times
   * are passed.  Best efforts are made to return an update response object,
   * but downstream failures may force a ResponseGeneric object instead.
   *
   * @return \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate|\Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseGeneric
   *   A response object.
   */
  public function retrieveUpdates($time_from = 0, $time_to = 0, array $params = []): ResponseGeneric {
    // Limit parameters to 'offset' and 'limit'.  The limit defaults to '0'.
    $params = array_intersect_key(
      $this->prepParams($params) + ['limit' => '0'],
      ['offset' => '', 'limit' => '']
    );
    $time_to = $this->formatTimestamp($time_to ?: time());
    $time_from = $this->formatTimestamp($time_from ?: ($time_to - 86400));
    $resource = "updates/$time_from/$time_to";

    /** @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate $ret */
    $ret = $this->generateResponse(
      $this->request->get($resource, $params),
      ApiResponseManager::OPENLEG_RESPONSE_TYPE_UPDATE
    );
    if (!($ret instanceof ResponseUpdate)) {
      $this->logger->warning('Fell back to generic response for updates: @type', ['@type' => $this->definition['id']]);
    }
    return $ret;
  }

  /**
   * {@inheritDoc}
   *
   * Best efforts are made to return a search response object, but downstream
   * failures may force a ResponseGeneric object instead.
   *
   * @return \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseGeneric|\Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch
   *   A response object.
   */
  public function retrieveSearch(string $search_term, array $params = []): ResponseGeneric {
    $params = $this->prepParams($params);

    $page = $params['page'] ?? 1;
    $limit = $params['limit'] ?? 0;
    $offset = $params['offset'] ?? 0;
    if (!$offset) {
      $offset = (($page - 1) * $limit) + 1;
    }
    $params = [
      'term' => urlencode($search_term),
      'offset' => $offset,
      'limit' => $limit,
      'page' => $page,
    ];

    $ret = $this->generateResponse(
      $this->request
        ->get("search", $params), ApiResponseManager::OPENLEG_RESPONSE_TYPE_SEARCH
    );
    if (!($ret instanceof ResponseSearch)) {
      $this->logger->warning('Fell back to generic response for search: @type', ['@type' => $this->definition['id']]);
    }
    return $ret;
  }

  /**
   * Executes after the request has been made.
   */
  protected function processResponse() {
  }

  /**
   * Formats a date for usage.
   *
   * @param string $date
   *   The date to format, acceptable for strtotime().
   * @param bool $include_time
   *   If the timestamp format should be used, versus the date-only format.
   *
   * @return string
   *   The formatted date, or a blank string.
   */
  protected function formatTimestamp(string $date = '', bool $include_time = TRUE): string {
    $format = $include_time ? $this->request::OPENLEG_TIME_FORMAT : $this->request::OPENLEG_DATE_FORMAT;
    if (!$date) {
      $date = $this->params['history'] ?? '';
    }
    $time = is_numeric($date) ? $date : strtotime($date);
    return $time ? (date($format, $time) ?: '') : '';
  }

  /**
   * Find the type-specific response to create, or creates a generic response.
   *
   * This method first tries to find a response plugin matching the request
   * plugin's id.  If one is not found, the general class for the type is
   * tried.  If that also fails, a ResponseGeneric is returned.
   *
   * @param object $response
   *   The full JSON-decoded response from Openleg.
   * @param string $type
   *   The type of response (e.g., 'item', 'search', 'update')
   *
   * @return \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseGeneric
   *   A response object.
   *
   * @see \Drupal\nys_openleg\Service\ApiResponseManager
   */
  protected function generateResponse(object $response, string $type = ApiResponseManager::OPENLEG_RESPONSE_TYPE_ITEM): ResponseGeneric {

    // If the plugin does not have a corresponding response, fallback
    // to the generic typed response.  If instantiation is throws,
    // fallback further to the generic untyped response.
    try {
      $name = $response->responseType ?? '';
      if (!$name) {
        $name = $this->responseManager->hasDefinition($this->definition['id'])
          ? $this->definition['id']
          : "response_$type";
      }

      /** @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseGeneric $ret */
      $ret = $this->responseManager->createInstance($name);

      $ret->init($response);
    }
    catch (\Throwable $e) {
      $ret = new ResponseGeneric($response);
    }

    return $ret;
  }

  /**
   * {@inheritDoc}
   */
  public function setParams(array $params): self {
    $this->params = $params;
    return $this;
  }

}
