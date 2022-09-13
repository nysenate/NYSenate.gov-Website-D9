<?php

namespace Drupal\migrate_upgrade;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\migrate\MigrateMessageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class DrushLogMigrateMessage.
 *
 * @package Drupal\migrate_upgrade
 */
class DrushLogMigrateMessage implements MigrateMessageInterface, LoggerAwareInterface {
  use LoggerAwareTrait;

  /**
   * The map between migrate status and drush log levels.
   *
   * @var array
   */
  protected $map = [
    'status' => 'notice',
  ];

  /**
   * DrushLogMigrateMessage constructor.
   */
  public function __construct(LoggerInterface $logger) {
    $this->setLogger($logger);
  }

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   *
   * @see drush_log()
   */
  public function display($message, $type = 'status') {
    $type = isset($this->map[$type]) ? $this->map[$type] : RfcLogLevel::NOTICE;
    \Drupal::service(('logger.channel.migrate_upgrade'))->log($type, $message);
  }

}
