<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Simple XML Sitemap logger.
 */
class Logger {

  use StringTranslationTrait;

  /**
   * Can be debug/info/notice/warning/error.
   */
  protected const LOG_SEVERITY_LEVEL_DEFAULT = 'notice';

  /**
   * Can be status/warning/error.
   */
  protected const DISPLAY_MESSAGE_TYPE_DEFAULT = 'status';

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The actual message.
   *
   * @var string
   */
  protected $message = '';

  /**
   * The actual substitutions.
   *
   * @var array
   */
  protected $substitutions = [];

  /**
   * Logger constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    LoggerInterface $logger,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user
  ) {
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
  }

  /**
   * Sets the message with substitutions.
   *
   * @param string $message
   *   Message to set.
   * @param array $substitutions
   *   Substitutions to set.
   *
   * @return $this
   */
  public function m(string $message, array $substitutions = []): Logger {
    $this->message = $message;
    $this->substitutions = $substitutions;
    return $this;
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param string $logSeverityLevel
   *   The severity level.
   *
   * @return $this
   */
  public function log(string $logSeverityLevel = self::LOG_SEVERITY_LEVEL_DEFAULT): Logger {
    $this->logger->$logSeverityLevel(strtr($this->message, $this->substitutions));

    return $this;
  }

  /**
   * Displays the message given the right permission.
   *
   * @param string $displayMessageType
   *   The message's type.
   * @param string $permission
   *   The permission to check for.
   *
   * @return $this
   */
  public function display(string $displayMessageType = self::DISPLAY_MESSAGE_TYPE_DEFAULT, string $permission = ''): Logger {
    if (empty($permission) || $this->currentUser->hasPermission($permission)) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $this->messenger->addMessage($this->t($this->message, $this->substitutions), $displayMessageType);
    }

    return $this;
  }

}
