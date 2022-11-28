<?php

namespace Drupal\nys_sage\Service;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\nys_sage\Logger\SageLogger;
use Drupal\nys_sage\Sage\Request;
use Drupal\nys_sage\Sage\Response;

/**
 * Service class for calling NYS SAGE API.
 */
class SageApi {

  use LoggerChannelTrait;

  /**
   * All cached responses, keyed by cache id.
   *
   * @var \Drupal\nys_sage\Sage\Request[]
   */
  protected static array $cachedRequests = [];

  /**
   * Local config for nys_sage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $localConfig;

  /**
   * The current request being processed.
   *
   * @var \Drupal\nys_sage\Sage\Request
   */
  protected Request $currentRequest;

  /**
   * SAGE logging facility.
   *
   * @var \Drupal\nys_sage\Logger\SageLogger
   */
  protected SageLogger $sageLogger;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config, SageLogger $sage_log) {
    $this->localConfig = $config->get('nys_sage.settings');
    $this->sageLogger = $sage_log;
  }

  /**
   * Adds a Response object to the current cache.
   *
   * @param string $cid
   *   The cache ID of the request which generated the response.
   * @param \Drupal\nys_sage\Sage\Request $request
   *   The response to cache.
   */
  public static function setCachedRequest(string $cid, Request $request): void {
    static::$cachedRequests[$cid] = $request;
  }

  /**
   * Fetches a previously recorded response from cache.
   *
   * @param string $cid
   *   The cache ID of the request which generated the response.
   *
   * @return \Drupal\nys_sage\Sage\Request|null
   *   The response.
   */
  public static function getCachedRequest(string $cid): ?Request {
    return static::$cachedRequests[$cid] ?? NULL;
  }

  /**
   * Creates an appropriate response object, given a group and method.
   *
   * @param string $group
   *   The desired group.
   * @param string $method
   *   The desired method.
   * @param string $curl_response
   *   Response text from curl.
   *
   * @return \Drupal\nys_sage\Sage\Response
   *   An appropriate Response object.
   */
  public static function createResponse(string $group, string $method, string $curl_response = ''): Response {
    $class_name = static::createFamilyName($group, $method, TRUE);
    return new $class_name($curl_response);
  }

  /**
   * Wrapper method to generate and execute a request.
   *
   * @param string $group
   *   The desired group.
   * @param string $method
   *   The desired method.
   * @param array $params
   *   Parameters to use for the request.
   *
   * @return \Drupal\nys_sage\Sage\Response
   *   The response of the executed request.
   */
  public function call(string $group, string $method, array $params = []): Response {
    // Ensure the API key and default districtStrategy are set.
    // Allow the caller to have precedence.
    $params += [
      'key' => $this->localConfig->get('api_key'),
    ];

    // Post a warning if no API key is found.
    if (!$params['key']) {
      $this->getLogger('nys_sage')
        ->warning('SageApi invoked with an empty API key');
    }

    // Generate the request object.
    $this->setRequest($this->createRequest($group, $method, $params));

    // Execute the request and return the response.
    return $this->getRequest()->execute();
  }

  /**
   * Wrapper to call for a district assignment.
   *
   * @param array $params
   *   An array of address parts, per SAGE API.
   *
   * @return string|null
   *   The district number, or NULL on missing/error.
   *
   * @see http://sage.nysenate.gov:8080/docs/html/index.html
   */
  public function districtAssign(array $params): ?string {
    // @todo Validate params, verify requirements, etc.
    $response = $this->call('district', 'assign', $params);
    return $response->districts->senate->district ?? NULL;
  }

  /**
   * Setter for the current request.
   */
  public function setRequest(Request $request): void {
    $this->currentRequest = $request;
  }

  /**
   * Creates an appropriate request given group and method, and optional params.
   *
   * @param string $group
   *   The desired group.
   * @param string $method
   *   The desired method.
   * @param array $params
   *   A key-value list of parameters.
   *
   * @return \Drupal\nys_sage\Sage\Request
   *   A Request object appropriate to group and method.
   */
  public function createRequest(string $group, string $method, array $params = []): Request {
    // Generate the request object.
    $class_name = static::createFamilyName($group, $method);
    return new $class_name($this->localConfig, $this->sageLogger, $params);
  }

  /**
   * Validates a group and method.
   *
   * @param string $group
   *   The desired group.
   * @param string $method
   *   The desired method.
   * @param bool $response
   *   TRUE if the name is for a response, FALSE for a request.
   *
   * @return string
   *   The class name to be instantiated.
   */
  public static function createFamilyName(string $group, string $method, bool $response = FALSE): string {
    $suffix = $response ? 'Response' : 'Request';
    $class_name = static::formatFamily($group, $method) . $suffix;
    $namespace = 'Drupal\\nys_sage\\Sage\\' . $suffix . 's\\';
    if (!class_exists($namespace . $class_name)) {
      $class_name = 'Generic' . $suffix;
    }
    return $namespace . $class_name;
  }

  /**
   * Standardized formatting for request/response object names.
   */
  public static function formatFamily(string $group, string $method): string {
    $ret = 'Generic';
    $group = ucfirst(strtolower($group));
    $method = ucfirst(strtolower($method));
    if ($group && $method) {
      $ret = $group . $method;
    }
    return $ret;
  }

  /**
   * Getter for the current request.
   */
  public function getRequest(): Request {
    return $this->currentRequest;
  }

}
