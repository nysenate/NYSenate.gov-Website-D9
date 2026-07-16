<?php

namespace Drupal\nys_feeds\Traits;

use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Trait to add functionality specific to formatting date/time.
 */
trait DateFormatterTrait {

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
   * Default timestamp format.
   */
  protected string $defaultDateFormat = 'Ymd\THis';

  /**
   * Wrapper to expose the DateFormatter service.
   */
  protected function dateFormatter(): DateFormatterInterface {
    return \Drupal::service('date.formatter');
  }

  /**
   * Sets the timezone.
   */
  public function setTimezone(string $timezone = ''): static {
    $this->timezone = $timezone ?: (
      \Drupal::currentUser()->getTimeZone()
      ?? \Drupal::config('system.date')->get('timezone.default')
    );

    return $this;
  }

  /**
   * Resolves the common date parameter into a DateTime object.
   *
   * Depends on the using class to own a FeedState property.
   */
  protected function initDateParam(): static {
    // Basic validation of the date.
    $date = \DateTimeImmutable::createFromFormat(
      'Ymd', $this->state->params['date'] ?? date('Ymd', time())
    );
    if ($date === FALSE) {
      $date = \DateTimeImmutable::createFromFormat('Ymd', date('Ymd', time()));
      $this->state->messages[] = "Invalid date parameter (must be YYYYMMDD), using " . $date->format("Ymd");
      $this->state->code = 400;
    }
    $this->state->params['date_obj'] = $date;

    return $this;
  }

  /**
   * Renders an epoch timestamp as a date/time string, considering timezone.
   *
   * @param int $date
   *   An epoch timestamp.
   * @param string|null $format
   *   An optional time/date format.  Defaults to self::$defaultDateFormat.
   */
  protected function formatDate(int $date, ?string $format = NULL): string {
    try {
      $ret = $this->dateFormatter()->format(
        $date,
        'custom',
        $format ?? $this->defaultDateFormat,
        $this->timezone ?? $this->defaultTimezone
      );
    }
    catch (\Exception) {
      $ret = 'Failed to parse date';
    }
    return $ret;
  }

}
