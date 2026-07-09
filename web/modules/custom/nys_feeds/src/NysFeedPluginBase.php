<?php

namespace Drupal\nys_feeds;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for NYS Feeds plugins.
 */
class NysFeedPluginBase implements NysFeedPluginInterface {

  /**
   * Drupal's Date Formatter Service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Instantiation configuration.
   *
   * @var array
   */
  protected array $config;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * The calculated time zone for the request (current user, or system default).
   *
   * @var string
   */
  protected string $timezone;

  /**
   * The default timezone to use, just in case.
   */
  protected string $defaultTimezone = 'America/New_York';

  /**
   * Constructor.
   */
  public function __construct(array $definition, array $config = []) {
    $this->definition = $definition;
    $this->config = $config;
    $this->dateFormatter = \Drupal::service('date.formatter');
    $this->timezone = \Drupal::currentUser()->getTimeZone()
      ?? \Drupal::config('system.date')->get('timezone.default');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($plugin_definition, $configuration);
  }

  /**
   * {@inheritDoc}
   *
   * Override in implementations.  This provides a default "no response".
   */
  public function getFeed(FeedState $state): FeedState {
    $state->data = ['No work to do'];
    return $state;
  }

  /**
   * Renders an epoch timestamp as a date/time string, considering timezone.
   *
   * @param int $date
   *   An epoch timestamp.
   * @param bool $include_time
   *   If TRUE, a time portion will be included in the return.
   */
  protected function formatDate(int $date, bool $include_time = TRUE): string {
    $format = 'Y-m-d' . ($include_time ? '\TH:i:s' : '');
    try {
      $ret = $this->dateFormatter->format(
        $date,
        'custom',
        $format,
        $this->timezone ?? $this->defaultTimezone
      );
    }
    catch (\Exception) {
      $ret = 'Failed to parse date';
    }
    return $ret;
  }

}
