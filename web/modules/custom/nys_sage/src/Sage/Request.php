<?php

namespace Drupal\nys_sage\Sage;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Config;
use Drupal\nys_sage\Logger\SageLogger;
use Drupal\nys_sage\Service\SageApi;

/**
 * A generic representation of a SAGE request.
 */
abstract class Request {

  /**
   * A list of parameter keys for a specific request.
   *
   * @var array|string[]
   */
  protected static array $knownParams = [];

  /**
   * A list of parameters expected on any request.
   *
   * @var string[]
   */
  protected static array $alwaysParams = [
    'key',
    'format',
    'callback',
  ];

  /**
   * Parameters passed from the caller.
   *
   * @var array
   */
  protected array $params = [];

  /**
   * The SAGE API group to call.
   *
   * @var string
   */
  protected string $group = '';

  /**
   * The SAGE API method to call.
   *
   * @var string
   */
  protected string $method = '';

  /**
   * Indicates if unknown parameters should be filtered out.
   *
   * @var bool
   */
  protected bool $filterParams = TRUE;

  /**
   * Config settings for nys_sage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * SAGE logging facility.
   *
   * @var \Drupal\nys_sage\Logger\SageLogger
   */
  protected SageLogger $sageLogger;

  /**
   * The Response object.
   *
   * @var \Drupal\nys_sage\Sage\Response|null
   */
  protected ?Response $response = NULL;

  /**
   * Constructor.
   */
  public function __construct(Config $config, SageLogger $sage_log, array $params = []) {
    $this->config = $config;
    $this->sageLogger = $sage_log;
    $this->params = $params;
  }

  /**
   * Executes a call to SAGE API.
   *
   * @param bool $refresh
   *   If TRUE, cached responses are ignored.
   * @param bool $force_log
   *   If the call should logged regardless of config setting.
   *
   * @return \Drupal\nys_sage\Sage\Response
   *   The call's Response.
   */
  public function execute(bool $refresh = FALSE, bool $force_log = FALSE): Response {

    // There may be a response already, or one from cache.
    $cid = $this->getCid();
    if ((!$this->response) && ($cached_request = SageApi::getCachedRequest($cid))) {
      $this->response = $cached_request->getResponse();
    }

    // If refresh, or no response exists, do the call.
    if ($refresh || !$this->response) {
      $this->response = $this->createResponse($this->executeCurl());
      if ($force_log || $this->config->get('logging')) {
        $this->sageLog();
      }

      // Cache the response.
      SageApi::setCachedRequest($cid, $this);
    }

    return $this->response;
  }

  /**
   * Calculates the cache ID for this request.
   *
   * The cache ID is the group, the method, and the array of known parameters,
   * concatenated with a colon.  The parameters will be built as a query
   * string, with the API key removed.
   *
   * @return string
   *   The cache ID for this request.
   */
  public function getCid(): string {
    $data = array_diff_key($this->processCallParams(), ['key' => '']);
    ksort($data);
    return $this->group . ':' . $this->method . ':' . UrlHelper::buildQuery($data);
  }

  /**
   * Pre-call processing of the parameters.
   *
   * This is meant to be overridden by children to massage the parameters
   * as required by the specific group/method.
   *
   * @return array
   *   The parameters to be used for the call.
   */
  protected function processCallParams(): array {
    return $this->filterCallParams();
  }

  /**
   * Filters the parameters.
   *
   * @param bool $known
   *   If TRUE, only the known parameters are returned.
   *   If FALSE, only the unknown parameters are returned.
   *
   * @return array
   *   An array of parameter key/value pairs.
   */
  protected function filterCallParams(bool $known = TRUE): array {
    $params = array_flip(static::getParams());
    $func = $known ? 'array_intersect_key' : 'array_diff_key';
    return $func($this->params, $params);
  }

  /**
   * Returns the list of parameters known for a request.
   */
  public static function getParams(): array {
    return array_unique(array_merge(static::$knownParams, static::$alwaysParams));
  }

  /**
   * Getter for response.
   */
  public function getResponse(): Response {
    return $this->response;
  }

  /**
   * Executes the curl call.
   *
   * @return string
   *   The curl response text.
   */
  protected function executeCurl(): string {
    // Build the url with query parameters.
    $params = $this->filterParams ? $this->processCallParams() : $this->getParam();
    $full_url = $this->constructUrl() . '?' . UrlHelper::buildQuery($params);

    // Build cURL options.
    $opts = [
      CURLOPT_URL => $full_url,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_SSL_VERIFYPEER => (boolean) ($this->config->get('ssl_verifypeer') ?? TRUE),
    ];

    // Execute the call.
    $c = curl_init();
    curl_setopt_array($c, $opts);
    $ret = curl_exec($c);
    curl_close($c);

    return $ret;
  }

  /**
   * Gets a single parameter, or all parameters if no name is passed.
   */
  public function getParam(string $name = '') {
    return $name ? ($this->params[$name] ?? '') : $this->params;
  }

  /**
   * Constructs the URL for a SAGE request.
   */
  protected function constructUrl(): string {
    $protocol = $this->config->get('use_ssl') ? 'https' : 'http';
    $base_uri = trim($this->config->get('api_endpoint'), '/');

    return $protocol . '://' . $base_uri . '/' . $this->group . '/' . $this->method;
  }

  /**
   * Logs the current request and response to the SAGE log.
   */
  protected function sageLog() {
    $params = $this->filterParams ? $this->processCallParams() : $this->getParam();
    $log = [
      'status' => $this->response->status ?? '',
      'method' => "$this->group/$this->method",
      'params_rcvd' => array_diff_key($this->getParam(), ['key' => '']),
      'environ' => ['server' => $_SERVER],
      'args' => array_diff_key($params, ['key' => '']),
      'response' => $this->response->getResponse(),
      'short_response' => $this->response->getShortResponse(),
    ];
    $this->sageLogger->log($this->prepLog($log));
  }

  /**
   * Customizes the log entry for this request.
   *
   * @param array $entry
   *   The initialized log entry.
   *
   * @return array
   *   The finalized log entry.
   */
  protected function prepLog(array $entry): array {
    return $entry;
  }

  /**
   * Creates a response appropriate for this request.
   *
   * @param string $curl_response
   *   Response text from curl.
   *
   * @return \Drupal\nys_sage\Sage\Response
   *   An appropriate Response object.
   */
  public function createResponse(string $curl_response): Response {
    return SageApi::createResponse($this->group, $this->method, $curl_response);
  }

  /**
   * Sets a parameter.  Chainable.
   *
   * @param string $name
   *   Name of the parameter to set.
   * @param mixed $val
   *   The value to set.
   *
   * @return $this
   */
  public function setParam(string $name, $val = ''): Request {
    $this->params[$name] = $val;
    return $this;
  }

  /**
   * Sets filtering behavior.  Chainable.
   */
  public function setFilter(bool $filter = TRUE): Request {
    $this->filterParams = $filter;
    return $this;
  }

}
