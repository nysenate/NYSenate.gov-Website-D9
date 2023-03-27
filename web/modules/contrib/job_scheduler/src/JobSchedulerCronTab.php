<?php

namespace Drupal\job_scheduler;

/**
 * Class for job scheduler crontab.
 */
class JobSchedulerCronTab implements JobSchedulerCronTabInterface {

  /**
   * Original crontab string or array.
   *
   * @var string|array
   */
  public $crontab;

  /**
   * Parsed numeric values indexed by type.
   *
   * @var array
   */
  public $cron;

  /**
   * Constructs a JobSchedulerCronTab object.
   *
   * About crontab strings, see all about possible formats
   * http://linux.die.net/man/5/crontab.
   *
   * @param string|array $crontab
   *   Crontab text line: minute hour day-of-month month day-of-week.
   */
  public function __construct($crontab) {
    $this->crontab = $crontab;
    if (is_array($crontab)) {
      $this->cron = $this->values($crontab);
    }
    else {
      $this->cron = $this->parse($crontab);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function parse($crontab) {
    // Replace multiple spaces by single space.
    $crontab = preg_replace('/(\s+)/', ' ', $crontab);
    // Expand into elements and parse all.
    $values = explode(' ', trim($crontab));

    return self::values($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function values(array $array) {
    if (count($array) == 5) {
      $values = array_combine(['minutes', 'hours', 'mday', 'mon', 'wday'], array_map('trim', $array));

      $elements = [];
      foreach ($values as $type => $string) {
        $elements[$type] = self::parseElement($type, $string, TRUE);
      }
      // Returns only if we have the right number of elements.
      // Dangerous means works running every second or things like that.
      if (count(array_filter($elements)) == 5) {
        return $elements;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nextTime($start_time = NULL, $limit = 366) {
    $start_time = $start_time ?? time();

    // Get minutes, hours, mday, wday, mon, year.
    $start_date = getdate($start_time);
    if ($date = $this->nextDate($start_date, $limit)) {
      return mktime($date['hours'], $date['minutes'], 0, $date['mon'], $date['mday'], $date['year']);
    }
    else {
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function nextDate(array $date, $limit = 366) {
    $date['seconds'] = 0;
    // It is possible that the current date doesn't match.
    if ($this->checkDay($date) && ($nextdate = $this->nextHour($date))) {
      return $nextdate;
    }
    elseif ($nextdate = $this->nextDay($date, $limit)) {
      return $nextdate;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function possibleValues($type) {
    switch ($type) {
      case 'minutes':
        return range(0, 59);

      case 'hours':
        return range(0, 23);

      case 'mday':
        return range(1, 31);

      case 'mon':
        return range(1, 12);

      case 'wday':
        // These are PHP values, not *nix ones.
        return range(0, 6);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function translateNames($type, $string) {
    switch ($type) {
      case 'wday':
        $replace = array_merge(
        // Tricky, tricky, we need sunday to be zero at the beginning of a
        // range, but 7 at the end.
          [
            '-sunday' => '-7',
            '-sun' => '-7',
            'sunday-' => '0-',
            'sun-' => '0-',
          ],
          array_flip([
            'sunday', 'monday', 'tuesday', 'wednesday',
            'thursday', 'friday', 'saturday',
          ]),
          array_flip([
            'sun', 'mon', 'tue',
            'wed', 'thu', 'fri', 'sat',
          ])
        );
        break;

      case 'mon':
        $replace = array_merge(
          array_flip([
            'nomonth1',
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december',
          ]),
          array_flip([
            'nomonth2',
            'jan',
            'feb',
            'mar',
            'apr',
            'may',
            'jun',
            'jul',
            'aug',
            'sep',
            'oct',
            'nov',
            'dec',
          ]),
          ['sept' => 9]
        );
        break;
    }
    if (empty($replace)) {
      return $string;
    }
    else {
      return strtr($string, $replace);
    }
  }

  /**
   * Checks whether date's day is a valid one.
   *
   * @param array $date
   *   Date array with: 'mday', 'mon', 'year', 'hours', 'minutes'.
   *
   * @return bool
   *   Returns true if the day is valid, false otherwise.
   */
  protected function checkDay(array $date) {
    foreach (['wday', 'mday', 'mon'] as $key) {
      if (!in_array($date[$key], $this->cron[$key])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Finds the next day from date that matches with cron parameters.
   *
   * Maybe it's possible that it's within the next years, maybe no day of a year
   * matches all conditions. However, to prevent infinite loops we restrict it
   * to the next year.
   *
   * @param array $date
   *   Date array with: 'mday', 'mon', 'year', 'hours', 'minutes'.
   * @param int $limit
   *   (optional) The time limit in days. Defaults to 366.
   *
   * @return array|false
   *   A date array, or false if there was an error.
   */
  protected function nextDay(array $date, $limit = 366) {
    // Safety check, we love infinite loops.
    $i = 0;
    while ($i++ <= $limit) {
      // This should fix values out of range, like month > 12, day > 31.
      // So we can trust we get the next valid day, can't we?
      $time = mktime(0, 0, 0, $date['mon'], $date['mday'] + 1, $date['year']);

      $date = getdate($time);
      if ($this->checkDay($date)) {
        $date['hours'] = reset($this->cron['hours']);
        $date['minutes'] = reset($this->cron['minutes']);
        return $date;
      }
    }

    return FALSE;
  }

  /**
   * Finds the next available hour within the same day.
   *
   * @param array $date
   *   Date array with: 'mday', 'mon', 'year', 'hours', 'minutes'.
   *
   * @return array|false
   *   A date array, or false if there was an error.
   */
  protected function nextHour(array $date) {
    $cron = $this->cron;
    while ($cron['hours']) {
      $hour = array_shift($cron['hours']);
      // Current hour; next minute.
      if ($date['hours'] == $hour) {
        foreach ($cron['minutes'] as $minute) {
          if ($date['minutes'] < $minute) {
            $date['hours'] = $hour;
            $date['minutes'] = $minute;
            return $date;
          }
        }
      }
      // Next hour; first avaiable minute.
      elseif ($date['hours'] < $hour) {
        $date['hours'] = $hour;
        $date['minutes'] = reset($cron['minutes']);
        return $date;
      }
    }

    return FALSE;
  }

  /**
   * Parses each text element. Recursive up to some point.
   *
   * @param string $type
   *   The element type. One of 'minutes', 'hours', 'mday', 'mon', 'wday'.
   * @param string $string
   *   The element string to parse.
   * @param bool $translate
   *   (optional) Whether or not to translate. Defaults to false.
   *
   * @return array
   *   The element string parsed into an array.
   */
  protected static function parseElement($type, $string, $translate = FALSE) {
    $string = trim($string);
    if ($translate) {
      $string = self::translateNames($type, $string);
    }
    if ($string === '*') {
      // This means all possible values, return right away, no need to double
      // check.
      return self::possibleValues($type);
    }
    elseif (strpos($string, '/')) {
      // Multiple. Example */2, for weekday will expand into 2, 4, 6.
      [$values, $multiple] = explode('/', $string);
      $values = self::parseElement($type, $values);
      foreach ($values as $value) {
        if (!($value % $multiple)) {
          $range[] = $value;
        }
      }
    }
    elseif (strpos($string, ',')) {
      // Now process list parts, expand into items, process each and merge back.
      $list = explode(',', $string);
      $range = [];
      foreach ($list as $item) {
        if ($values = self::parseElement($type, $item)) {
          $range = array_merge($range, $values);
        }
      }
    }
    elseif (strpos($string, '-')) {
      // This defines a range. Example 1-3, will expand into 1,2,3.
      [$start, $end] = explode('-', $string);
      // Double check the range is within possible values.
      $range = range($start, $end);
    }
    elseif (is_numeric($string)) {
      // This looks like a single number, double check it's int.
      $range = [(int) $string];
    }

    // Return unique sorted values and double check they're within possible
    // values.
    if (!empty($range)) {
      $range = array_intersect(array_unique($range), self::possibleValues($type));
      sort($range);
      // Sunday validation. We need cron values to match PHP values, thus week
      // day 7 is not allowed, must be 0.
      if ($type == 'wday' && in_array(7, $range)) {
        array_pop($range);
        array_unshift($range, 0);
      }
      return $range;
    }
    else {
      // No match found for this one, will produce an error with validation.
      return [];
    }
  }

}
