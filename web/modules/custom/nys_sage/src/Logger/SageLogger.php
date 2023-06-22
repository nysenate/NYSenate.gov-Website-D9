<?php

namespace Drupal\nys_sage\Logger;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Prepares and writes messages to the SAGE log.
 *
 * @see nys_sage_schema()
 */
class SageLogger {

  use LoggerChannelTrait;

  /**
   * Number of seconds in one day.
   */
  const SAGE_ONE_DAY = 60 * 60 * 24;

  /**
   * The fields of the logging table, with default value if applicable.
   *
   * @var string[]
   */
  protected static array $fields = [
    'status' => NULL,
    'method' => NULL,
    'params_rcvd' => NULL,
    'environ' => NULL,
    'args' => NULL,
    'response' => NULL,
    'short_response' => '',
  ];

  /**
   * A database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $db;

  /**
   * Watchdog channel for SAGE.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Config settings for nys_sage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Drupal's current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new SageLogger instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config object for nys_sage.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The current Drupal user.
   */
  public function __construct(Connection $database, ConfigFactory $config, AccountProxyInterface $user) {
    $this->db = $database;
    $this->logger = $this->getLogger('nys_sage');
    $this->config = $config->get('nys_sage.settings');
    $this->currentUser = $user;
  }

  /**
   * Wrapper method for cron-based log table maintenance.
   */
  public function cron() {
    $this->expireEntries($this->config->get('maximum_retention') * self::SAGE_ONE_DAY);
  }

  /**
   * Deletes records from the nys_sage_log table.
   *
   * @param int $max_age
   *   Any entry over this age (in seconds) will be deleted.  If this is less
   *   than one, TRUNCATE will be used instead of DELETE.
   */
  public function expireEntries(int $max_age = 0) {
    try {
      if ($max_age < 0) {
        $max_age = 0;
      }
      if ($max_age) {
        $this->db->delete('nys_sage_log')
          ->condition('timestamp', time() - $max_age, '<')
          ->execute();
      }
      else {
        $this->db->truncate('nys_sage_log')->execute();
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Failed to expire SAGE log entries', ['%max_age' => $max_age]);
    }
  }

  /**
   * Logs an entry to the nys_sage_log table.
   */
  public function log(array $data) {
    $this->doLog($this->resolveStandardFields($data));
  }

  /**
   * A direct, unconditional log entry.
   */
  public function doLog($fields) {
    try {
      $this->db
        ->insert('nys_sage_log')
        ->fields($fields)
        ->execute();
    }
    catch (\Throwable $e) {
      $this->logger->error('Could not log to nys_sage_log', ['%fields' => $fields]);
    }
  }

  /**
   * Provides for a "standard" set of fields and default values.
   */
  public function resolveStandardFields($data = []): array {
    // Time is always now. Fields are limited to the ones known in $fields.
    // The "optional" fields receive a sane default if not present.
    $data = ['timestamp' => time(), 'uid' => $this->currentUser->id()]
        + array_intersect_key($data, static::$fields)
        + static::getDefaults();

    // Encode all the fields that may hold objects or arrays.
    $encode = ['params_rcvd', 'environ', 'args', 'response', 'short_response'];
    foreach ($encode as $val) {
      $data[$val] = json_encode($data[$val] ?? NULL);
    }
    return $data;
  }

  /**
   * Returns an array with the default values for fields in a SAGE log entry.
   */
  public static function getDefaults(): array {
    return array_filter(
          static::$fields,
          function ($v) {
              return !is_null($v);
          }
      );
  }

  /**
   * Truncates the SAGE logging table.
   */
  public static function truncate() {
    \Drupal::getContainer()->get('sage_logger')->expireEntries();
  }

}
