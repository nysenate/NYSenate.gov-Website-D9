<?php

namespace Drupal\job_scheduler;

/**
 * Provides an interface for JobSchedulerCronTab.
 */
interface JobSchedulerCronTabInterface {

  /**
   * Parses a full crontab string into an array of type => values.
   *
   * Note this one is static and can be used to validate values.
   *
   * @param string $crontab
   *   The crontab string to parse.
   *
   * @return array
   *   The parsed crontab array.
   */
  public static function parse($crontab);

  /**
   * Parses an array of values, check whether this is valid.
   *
   * @param array $array
   *   A crontab array to validate.
   *
   * @return null|array
   *   The validated elements or null if the input was invalid.
   */
  public static function values(array $array);

  /**
   * Finds the next occurrence within the next year as unix timestamp.
   *
   * @param int $start_time
   *   (optional) Starting time. Defaults to null.
   * @param int $limit
   *   (optional) The time limit in days. Defaults to 366.
   *
   * @return int|false
   *   The next occurrence as a unix timestamp, or false if there was an error.
   */
  public function nextTime($start_time = NULL, $limit = 366);

  /**
   * Finds the next occurrence within the next year as a date array.
   *
   * @param array $date
   *   Date array with: 'mday', 'mon', 'year', 'hours', 'minutes'.
   * @param int $limit
   *   (optional) The time limit in days. Defaults to 366.
   *
   * @return array|false
   *   A date array, or false if there was an error.
   *
   * @see getdate()
   */
  public function nextDate(array $date, $limit = 366);

  /**
   * Get values for each type.
   *
   * @param string $type
   *   The element type. One of 'minutes', 'hours', 'mday', 'mon', 'wday'.
   *
   * @return array
   *   An array on integers specifying the range of the provided type.
   */
  public static function possibleValues($type);

  /**
   * Replaces element names with values.
   *
   * @param string $type
   *   The element type. One of 'wday' or 'mon'.
   * @param string $string
   *   The element string to translate.
   *
   * @return string
   *   The translated string.
   */
  public static function translateNames($type, $string);

}
