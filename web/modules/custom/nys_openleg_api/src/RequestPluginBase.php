<?php

namespace Drupal\nys_openleg_api;

use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for OpenLeg request plugins.
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
   * @var \Drupal\nys_openleg_api\Request
   */
  protected Request $request;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Plugin configuration passed during construction.
   *
   * @var array
   */
  protected array $pluginConfig;

  /**
   * Constructor.
   */
  public function __construct(array $definition, array $configuration, LoggerChannel $logger) {
    $this->definition = $definition;
    $this->pluginConfig = $configuration;
    $this->logger = $logger;
    $this->endpoint = $definition['endpoint'];
    $this->request = new Request($this->endpoint, $configuration['request_options'] ?? [], $this->logger);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): RequestPluginInterface {
    return new static(
      $plugin_definition,
      $configuration,
      $container->get('openleg_api.logger'),
    );
  }

  /**
   * Prepares parameters for inclusion in a query string.
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
  public function retrieve(string $name, $params = []): ?object {
    $params = $this->prepParams($params);
    return $this->request->get($this->normalizeName($name), $params);
  }

  /**
   * Normalizes the name for the request.  By default, nothing is done.
   */
  protected function normalizeName(string $name): string {
    return $name;
  }

  /**
   * {@inheritDoc}
   *
   * This implementation defaults to retrieving the past 24 hours if no times
   * are passed.
   */
  public function retrieveUpdates(mixed $time_from, mixed $time_to, array $params = []): ?object {
    // Only accept 'offset', 'limit', and 'detail' as parameters.
    // Default the limit parameter to '0'.
    $params = array_intersect_key(
      $this->prepParams($params) + ['limit' => '0'],
      ['offset' => '', 'limit' => '', 'detail' => '']
    );

    // If end time cannot be parsed, set it to now.
    $datetime_to = $this->normalizeTimestamp($time_to ?: microtime(TRUE))
      ?: $this->normalizeTimestamp('now');

    // If from time cannot be parsed, set it to 1 day ago.
    $datetime_from = $this->normalizeTimestamp($time_from)
      ?: $datetime_to->sub(new \DateInterval('P1D'));

    // Create the resource part of the URL.
    $f_time_to = $this->formatTimestamp($datetime_to);
    $f_time_from = $this->formatTimestamp($datetime_from);
    $resource = "updates/$f_time_from/$f_time_to";

    return $this->request->get($resource, $params);
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveSearch(string $search_term, array $params = []): ?object {
    $params = $this->prepParams($params);

    $page = $params['page'] ?? 1;
    $limit = $params['limit'] ?? ($params['per_page'] ?? 0);
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

    return $this->request->get("search", $params);
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
   * Coalesces possible timestamp formats into a DateTime object.
   *
   * @param string $timestamp
   *   A timestamp in any format parsable by strtotime().  If this is in the
   *   standard OpenLeg format, or is an epoch timestamp with a decimal portion,
   *   microseconds will be preserved.
   *
   * @return \DateTime|false
   *   Returns FALSE if unable to parse timestamp or create object.
   */
  protected function normalizeTimestamp(string $timestamp = ''): \DateTime|false {
    // If timestamp is numeric, assume an epoch timestamp.  Prefix with '@'.
    // Ensure a microseconds portion exists to avoid the "true zero" edge case.
    if (is_numeric($timestamp)) {
      $timestamp = '@' . $timestamp . (str_contains($timestamp, '.') ? '' : '.000000');
    }

    // Try to create using the detected format.  Failures return FALSE.
    try {
      $dt = new \DateTime($timestamp);
      $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }
    catch (\Throwable $e) {
      $parts = ['@ts' => $timestamp, '@msg' => $e->getMessage()];
      $msg = 'Could not normalize timestamp (@ts) @msg';
      $this->logger->error($msg, $parts);
      $dt = FALSE;
    }

    return $dt;
  }

  /**
   * {@inheritDoc}
   */
  public function setParams(array $params): static {
    $this->params = $params;
    return $this;
  }

}
