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
  protected function initDateParam(): void {
    $today = date('Ymd', time());
    // Basic validation of the date.  If none, use today and save it.
    $param = $this->resolvedParams['date'] ?? '';
    if (!$param) {
      $param = $this->resolvedParams['date'] = $today;
      $this->output->messages[] = "Using date: $today";
    }

    // Try the date.  If it fails, use today, save it, and set an error.
    $date = \DateTimeImmutable::createFromFormat('Ymd', $param);
    if ($date === FALSE) {
      $date = \DateTimeImmutable::createFromFormat('Ymd', $today);
      $this->resolvedParams['date'] = $today;
      $this->output->messages[] = "Invalid date parameter (must be YYYYMMDD), using " . $date->format("Ymd");
      $this->output->statusCode = 400;
    }

    // Make sure the actual date used is in params, and set the vars entry.
    // To ensure the url.query_arg cache context works, write the date back
    // to the request.
    $this->input->vars['date'] = $date->format("Ymd");
    $this->input->vars['date_obj'] = $date;
    $this->input->request()->query->set('date', $this->input->vars['date']);
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
