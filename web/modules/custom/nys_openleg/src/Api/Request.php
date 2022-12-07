<?php

namespace Drupal\nys_openleg\Api;

use SendGrid\Client;

/**
 * Class ApiRequest.
 *
 * This class is a wrapper around \Sendgrid\Client.
 *
 * The Client object enforces a static "host/version" format in the URL
 * it builds.  This class works around that by building the host portion
 * with the help of a "path prefix", inserted between the actual host and
 * the version.  The host actually sent to the Client constructor will be:
 *
 * https://<host>/<path_prefix>/
 *
 * Class constants provide for transparent default configuration for
 * host, version, and path prefix.  All of these items may be overridden
 * using setters, or the $options parameter in __construct().   All
 * setters return $this to allow for chaining.
 *
 * If no API key is found, the "key" parameter will not be added to the
 * query string.  An API key passed in get() will take precedence over
 * the object's property.
 *
 * Note that the endpoint and API key are not validated.  Improper
 * values in either of these will likely return an HTTP 404, or an
 * ApiResponse\Error object.
 *
 * @todo decide how to handle non-200 responses
 */
class Request {

  /**
   * The timestamp format expected by OL in most queries.
   */
  const OPENLEG_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

  /**
   * The date-only format expected by OL.
   */
  const OPENLEG_DATE_FORMAT = 'Y-m-d';

  const DEFAULT_HOST = 'legislation.nysenate.gov';

  const DEFAULT_VERSION = '3';

  const DEFAULT_PATH_PREFIX = ['api'];

  /**
   * An API key to be used by default with any future call.
   *
   * @var string
   */
  protected static string $defaultApiKey = '';

  /**
   * API key currently in use.
   *
   * @var string
   */
  protected string $apiKey;


  /**
   * Host for API calls.
   *
   * @var string
   */
  protected string $host;

  /**
   * Version for API calls.
   *
   * @var string
   */
  protected string $version;

  /**
   * A string, or array of strings, indicating current prefix.
   *
   * @var string|string[]
   */
  protected $pathPrefix;

  /**
   * A string, or array of strings, indicating endpoints.
   *
   * @var string|string[]
   */
  protected $endpoint;

  /**
   * Internal instantiation of the API client.
   *
   * @var \SendGrid\Client
   */
  protected Client $client;

  /**
   * Api constructor.
   *
   * Receives an endpoint (as a string, or an array of
   * strings; see buildPathArray()), and an array of options.
   *
   * Known options:
   *  - host:        the hostname of the server
   *  - path_prefix: the path portion after the host, but before version
   *  - version:     the API version to use
   *  - api_key:     the API key to use for just this instance.
   *
   * @param string|string[] $endpoint
   *   A string or array of strings (path array) specifying the API endpoint.
   * @param array $options
   *   Connection options.
   */
  public function __construct($endpoint = '', array $options = []) {
    // Initialize endpoint and options.
    $this->setEndpoint($endpoint)
      ->setApiKey($options['api_key'] ?? '')
      ->setHost($options['host'] ?? static::DEFAULT_HOST)
      ->setVersion($options['version'] ?? static::DEFAULT_VERSION)
      ->setPathPrefix($options['path_prefix'] ?? static::DEFAULT_PATH_PREFIX);
  }

  /**
   * Sets the API key for the next call.
   *
   * @param string $api_key
   *   The API key to use for future calls.
   *
   * @return $this
   */
  public function setApiKey(string $api_key = ''): Request {
    $this->apiKey = $api_key ?: static::$defaultApiKey;
    return $this;
  }

  /**
   * A convenience wrapper around (new self())->get().
   *
   * The parameters are as with __construct(), with two additional keys
   * in $options:
   *
   *  - resource: the resource parameter for get()
   *  - params:   the params parameter for get()
   *
   * @param string|string[] $endpoint
   *   A string or array of strings (path array) specifying the API endpoint.
   * @param array $options
   *   Connection options.
   *
   * @return object
   *   JSON-decoded response (could be NULL)
   */
  public static function fetch($endpoint = '', array $options = []): object {
    $request = new static($endpoint, $options);
    return $request->get($options['resource'] ?? NULL, $options['params'] ?? []);
  }

  /**
   * Instantiates an API client based on the host and path for this call.
   *
   * The URL to be called is built as:
   *
   * Https://<host>/<path_prefix>/<version>/<endpoint>/<resource>?<params>
   *
   * @param string|string[] $resource
   *   Resource to fetch; last part of the URL.
   * @param array $params
   *   Query parameters to add to the call.
   *
   * @return object
   *   JSON-decoded response (could be NULL)
   */
  public function get($resource = NULL, array $params = []): object {
    // Build the primary URL.
    $url = $this->buildHost();
    $resource = implode('/', $this->buildPathArray($resource)) . '/';
    $extra_path = $this->buildEndpoint() . $resource;

    // Build URL parameters.
    $params += $this->apiKey ? ['key' => $this->apiKey] : [];

    // Instantiate the client and make the call.
    $this->client = new Client($url, NULL, $this->getVersion(), [$extra_path]);

    // @todo decide on error-handling here, or in caller?
    // simulate an Error response?
    $response = $this->client->get('', $params);

    return json_decode($response->body());
  }

  /**
   * Builds a host string, including host and "path prefix".
   */
  protected function buildHost(): string {
    $host = [
      'https:/',
      $this->getHost(),
      ($this->getPathPrefix() ?: ''),
    ];
    return implode('/', array_filter($host)) . '/';
  }

  /**
   * Gets the host.
   */
  public function getHost(): string {
    return $this->host;
  }

  /**
   * Sets the host as provided, or the default host.
   */
  public function setHost(string $host = ''): Request {
    if (!$host) {
      $host = static::DEFAULT_HOST;
    }
    $this->host = trim($host, '/');
    return $this;
  }

  /**
   * Returns the path prefix as either a string or array.
   */
  public function getPathPrefix($as_array = FALSE) {
    if ($as_array) {
      $ret = $this->pathPrefix;
    }
    else {
      $ret = implode('/', $this->pathPrefix);
    }

    return $ret;
  }

  /**
   * Sets the path prefix.
   */
  public function setPathPrefix($path_prefix): Request {
    $this->pathPrefix = $this->buildPathArray($path_prefix);
    return $this;
  }

  /**
   * Builds a path array from a slash-delimited string, or an existing array.
   *
   * A path array is defined as an array of strings, with each member value
   * being one part of file/URL path.  This function can take either a slash-
   * delimited string or an existing array of strings as its parameter.  The
   * return is filtered to remove blank parts.
   *
   * @param array|string $path
   *   A string or array of strings (path parts).
   *
   * @return array
   *   A path, broken into an array by '/'.
   */
  protected function buildPathArray($path = ''): array {
    if (!is_array($path)) {
      $path = explode('/', trim($path, '/'));
    }
    return array_values(array_filter($path));
  }

  /**
   * Builds the endpoint portion of the call's URL.
   *
   * May also reset the current endpoint, if a new one is passed.  The return is
   * always terminated with '/'.
   *
   * @param string|string[]|null $endpoint
   *   A string or array of strings (path parts).
   *
   * @return string
   *   A built path string.
   */
  protected function buildEndpoint($endpoint = NULL): string {
    if (is_null($endpoint)) {
      $endpoint = $this->endpoint;
    }
    else {
      $this->setEndpoint($endpoint);
    }

    // Make sure it ends with a '/', per OpenLeg docs.
    // @see https://legislation.nysenate.gov/static/docs/html/laws.html#get-a-law-sub-document
    return implode('/', ($this->buildPathArray($endpoint) ?: [])) . '/';
  }

  /**
   * Get the API version.
   */
  public function getVersion(): string {
    return $this->version;
  }

  /**
   * Sets the API version.
   */
  public function setVersion(string $version): Request {
    $this->version = trim($version, '/');
    return $this;
  }

  /**
   * Sets the default API key for all future calls.
   */
  public static function useKey(string $api_key) {
    self::$defaultApiKey = $api_key;
  }

  /**
   * Gets the endpoint for the current call.
   */
  public function getEndpoint() {
    return $this->endpoint;
  }

  /**
   * Sets the endpoint.
   */
  public function setEndpoint($endpoint): Request {
    $this->endpoint = $this->buildPathArray($endpoint);
    return $this;
  }

  /**
   * Sets the internal options for the next API call.
   *
   * The $options array recognizes 'api_key', 'host', 'version', 'path_prefix'.
   *
   * @param array $options
   *   An array of connection options.
   *
   * @return $this
   */
  public function setOptions(array $options): Request {
    $this->setApiKey($options['api_key'] ?? '')
      ->setHost($options['host'] ?? static::DEFAULT_HOST)
      ->setVersion($options['version'] ?? static::DEFAULT_VERSION)
      ->setPathPrefix($options['path_prefix'] ?? static::DEFAULT_PATH_PREFIX);
    return $this;
  }

}
