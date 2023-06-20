<?php

namespace Drupal\nys_openleg\Api;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate;
use Drupal\nys_openleg\Service\ApiResponseManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   */
  public function retrieve(string $name, $params = []): ResponsePluginBase {
    $params = $this->prepParams($params);
    return $this->generateResponse($this->request->get($name, $params));
  }

  /**
   * {@inheritDoc}
   *
   * This implementation defaults to retrieving the past 24 hours if no times
   * are passed.
   *
   * @return \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate
   *   A response object.
   */
  public function retrieveUpdates($time_from = 0, $time_to = 0, array $params = []): ResponseUpdate {
    // Only accept 'offset', 'limit', and 'detail' as parameters.
    // Default the limit parameter to '0'.
    $params = array_intersect_key(
          $this->prepParams($params) + ['limit' => '0'],
          ['offset' => '', 'limit' => '', 'detail' => '']
      );

    // If no end time was passed, set it to now.
    $time_to = $this->normalizeTimestamp($time_to ?: microtime(TRUE));
    $f_time_to = $this->formatTimestamp($time_to);
    // From time defaults to one day ago.
    $time_from = $time_from
        ? $this->normalizeTimestamp($time_from)
        : $time_to->sub(new \DateInterval('P1D'));
    $f_time_from = $this->formatTimestamp($time_from);
    $resource = "updates/$f_time_from/$f_time_to";

    return $this->generateResponse(
          $this->request->get($resource, $params),
          ApiResponseManager::OPENLEG_RESPONSE_TYPE_UPDATE
      );
  }

  /**
   * {@inheritDoc}
   *
   * Best efforts are made to return a search response object, but downstream
   * failures may force a ResponseGeneric object instead.
   *
   * @return \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch
   *   A response object.
   */
  public function retrieveSearch(string $search_term, array $params = []): ResponseSearch {
    $params = $this->prepParams($params);

    $page = $params['page'] ?? 1;
    $limit = $params['limit'] ?? 0;
    $offset = $params['offset'] ?? 0;
    if (!$offset) {
      $offset = (($page - 1) * $limit) + 1;
    }
    $params = [
      'term' => $search_term,
      'offset' => $offset,
      'limit' => $limit,
      'page' => $page,
    ];

    return $this->generateResponse(
          $this->request->get("search", $params),
          ApiResponseManager::OPENLEG_RESPONSE_TYPE_SEARCH
      );
  }

  /**
   * Formats a date for usage.
   *
   * @param mixed|null $dt
   *   If this is not a \DateTime object, it will be normalized to one.
   * @param bool $include_time
   *   If the timestamp format should be used, versus the date-only format.
   *
   * @return string
   *   The formatted date, or a blank string.
   */
  protected function formatTimestamp(mixed $dt = NULL, bool $include_time = TRUE): string {
    if (!($dt instanceof \DateTimeInterface)) {
      $dt = $this->normalizeTimestamp($dt ?: ($this->params['history'] ?? ''));
    }
    $format = $include_time ? $this->request::OPENLEG_TIME_FULL : $this->request::OPENLEG_DATE_FORMAT;
    return $dt->format($format);
  }

  /**
   * Coalesces possible timestamp formats into a DateTimeImmutable object.
   *
   * @param string $timestamp
   *   A timestamp in any format parsable by strtotime().  If this is in the
   *   standard OpenLeg format, or is an epoch timestamp with a decimal portion,
   *   microseconds will be preserved.
   */
  protected function normalizeTimestamp(string $timestamp = ''): \DateTimeImmutable {
    // If timestamp is numeric, avoid the "true zero" edge case.
    if (is_numeric($timestamp)) {
      $normalize = $timestamp . (str_contains($timestamp, '.') ? '' : '.000000');
      $format = 'U.u';
    }
    // If not numeric, try the OpenLeg format first.
    else {
      $normalize = str_replace(' ', 'T', $timestamp);
      $format = $this->request::OPENLEG_TIME_FULL;
    }

    try {
      // Try to create using the detected format.  On failure, let DateTime
      // try to figure it out (and lose microsecond precision).
      $tz = new \DateTimeZone(date_default_timezone_get());
      $dt = \DateTimeImmutable::createFromFormat($format, $normalize, $tz)
            ?: new \DateTimeImmutable($timestamp, $tz);
    }
    catch (\Throwable $e) {
      // No options left.  Everyone out of the pool.
      throw new \InvalidArgumentException('Could not parse timestamp: ' . $timestamp);
    }

    return $dt;
  }

  /**
   * Finds a type-specific response plugin which matches the API response.
   *
   * Generally, every Openleg response is unique respective to resource type
   * and request type.  The resource type is usually indicated by the request
   * plugin's endpoint annotation (e.g., statute, transcript, etc).  The
   * request type is one of (item | update | search), depending on other
   * parameters of the request.  Each response has a responseType property,
   * which is unique to that resource/request_type combination. (Exception: all
   * search requests return a responseType of 'search-results list', regardless
   * of resource type)
   *
   * To find an appropriate response plugin, the explicit responseType property
   * of the API response is tried first.  If that definition does not exist, a
   * string comprised of the request's plugin ID and $type is tried.  If no
   * definition is found, the fallback of "response_$type" will be used.
   *
   * This means that if a plugin ID exactly matches the Openleg responseType
   * property, it will be the preferred plugin.
   *
   * @param object $response
   *   The full JSON-decoded response from Openleg.
   * @param string $type
   *   The type of response (e.g., 'item', 'search', 'update')
   *
   * @return \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseItem|\Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate|\Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch
   *   A response object appropriate to the Openleg responseType or $type.
   *
   * @see \Drupal\nys_openleg\Service\ApiResponseManager
   */
  protected function generateResponse(
        object $response,
        string $type = ApiResponseManager::OPENLEG_RESPONSE_TYPE_ITEM
    ): ResponsePluginBase {

    // Check if a response-specific plugin exists.  Look for definitions named
    // after the responseType value (preferred), or the alternate comprised of
    // this plugin's ID and $type.  If neither definition exists, fallback to
    // the generic "response_$type" plugin.
    $names = [
      'by_type' => $response->responseType ?? '',
      'by_name' => $this->definition['id'] . '_' . $type,
      'fallback' => "response_$type",
    ];

    $ret = NULL;
    try {
      foreach ($names as $name) {
        if ((!$ret) && $this->responseManager->hasDefinition($name)) {
          $ret = $this->responseManager->createInstance($name);
          break;
        }
      }
      $ret->init($response);
    }
    catch (PluginException $e) {
      $this->logger->error(
            "Failed to instantiate response object",
            [
              '@by_type' => $names['by_type'],
              '@by_name' => $names['by_name'],
              '@fallback' => $names['fallback'],
              '@last_try' => $name,
              '@message' => $e->getMessage(),
            ]
        );
      $ret = NULL;
    }
    /**
* @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseItem|\Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseUpdate|\Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch $ret
*/
    return $ret;
  }

  /**
   * {@inheritDoc}
   */
  public function setParams(array $params): RequestPluginBase {
    $this->params = $params;
    return $this;
  }

}
