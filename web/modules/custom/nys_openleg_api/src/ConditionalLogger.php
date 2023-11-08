<?php

namespace Drupal\nys_openleg_api;

use Drupal\Core\Config\Config;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Wrapper around Drupal's LoggerChannel logging based on configured level.
 */
class ConditionalLogger extends LoggerChannel {

  /**
   * A default level to use if config has not been set.
   *
   * @var int
   */
  public static int $defaultLogLevel = RfcLogLevel::WARNING;

  /**
   * The current log level.
   *
   * @var int
   */
  protected int $logLevel = RfcLogLevel::WARNING;

  /**
   * Config for openleg_api.settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * {@inheritDoc}
   *
   * Adds parameters for the OpenLeg API configuration, and conditional logger.
   */
  public function __construct(string $channel, Config $openlegConfig, LoggerChannelFactory $factory) {
    parent::__construct($channel);
    $this->addLogger($factory->get($channel));
    $this->config = $openlegConfig;
    $this->setLogLevel();
  }

  /**
   * Getter for LogLevel.
   */
  public function getLogLevel(): int {
    return $this->logLevel ?? static::$defaultLogLevel;
  }

  /**
   * Gets the name associated with the log level.
   *
   * @see \Drupal\Core\Logger\LoggerChannel::$levelTranslation
   */
  public function getLogLevelName(): string {
    return array_search($this->getLogLevel(), $this->levelTranslation) ?: '';
  }

  /**
   * Setter for LogLevel; determines which messages are logged.
   *
   * @param mixed $log_level
   *   Can be an integer, or a named log level ('warn', 'info', etc.), or NULL.
   *   If NULL, the config value is used.  It will fall back to 'warning' if a
   *   name cannot be translated, or if config is not set.
   *
   * @see \Drupal\Core\Logger\LoggerChannel::$levelTranslation
   */
  public function setLogLevel(mixed $log_level = NULL): void {
    if (is_null($log_level)) {
      $log_level = $this->config->get('log_level') ?? static::$defaultLogLevel;
    }
    if (!is_numeric($log_level)) {
      $log_level = $this->levelTranslation[$log_level] ?? static::$defaultLogLevel;
    }
    $this->logLevel = $log_level;
  }

  /**
   * {@inheritDoc}
   *
   * If the passed level cannot be reasonably compared to the configured level
   * (e.g., a non-standard/undefined name is passed), the entry will be logged.
   */
  public function log($level, $message, array $context = []): void {
    $this_level = $this->levelTranslation[$level] ?? -1;
    if ($this_level <= $this->getLogLevel()) {

      // For Drush's logger, which doesn't like numeric levels.
      if (!array_key_exists('_level', $context)) {
        $context['_level'] = $level;
      }

      parent::log($level, $message, $context);
    }
  }

}
