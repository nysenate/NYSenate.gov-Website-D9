<?php

namespace Drupal\security_review;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;

/**
 * A class containing static methods regarding the module's configuration.
 */
class SecurityReview {

  use DependencySerializationTrait;

  /**
   * Temporary logging setting.
   *
   * @var null|bool
   */
  protected static $temporaryLogging = NULL;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The state storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a SecurityReview instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user) {
    // Store the dependencies.
    $this->configFactory = $config_factory;
    $this->config = $config_factory->getEditable('security_review.settings');
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * Returns whether the module has been configured.
   *
   * If the module has been configured on the settings page this function
   * returns true. Otherwise it returns false.
   *
   * @return bool
   *   A boolean indicating whether the module has been configured.
   */
  public function isConfigured() {
    return $this->config->get('configured') === TRUE;
  }

  /**
   * Returns true if logging is enabled, otherwise returns false.
   *
   * @return bool
   *   A boolean indicating whether logging is enabled.
   */
  public function isLogging() {
    // Check for temporary logging.
    if (static::$temporaryLogging !== NULL) {
      return static::$temporaryLogging;
    }

    return $this->config->get('log') === TRUE;
  }

  /**
   * Returns the last time Security Review has been run.
   *
   * @return int
   *   The last time Security Review has been run.
   */
  public function getLastRun() {
    return $this->state->get('last_run', 0);
  }

  /**
   * Returns the IDs of the stored untrusted roles.
   *
   * @return string[]
   *   Stored untrusted roles' IDs.
   */
  public function getUntrustedRoles() {
    return $this->config->get('untrusted_roles');
  }

  /**
   * Sets the 'configured' flag.
   *
   * @param bool $configured
   *   The new value of the 'configured' setting.
   */
  public function setConfigured($configured) {
    $this->config->set('configured', $configured);
    $this->config->save();
  }

  /**
   * Sets the 'logging' flag.
   *
   * @param bool $logging
   *   The new value of the 'logging' setting.
   * @param bool $temporary
   *   Whether to set only temporarily.
   */
  public function setLogging($logging, $temporary = FALSE) {
    if (!$temporary) {
      $this->config->set('log', $logging);
      $this->config->save();
    }
    else {
      static::$temporaryLogging = ($logging == TRUE);
    }
  }

  /**
   * Sets the 'last_run' value.
   *
   * @param int $last_run
   *   The new value for 'last_run'.
   */
  public function setLastRun($last_run) {
    $this->state->set('last_run', $last_run);
  }

  /**
   * Stores the given 'untrusted_roles' setting.
   *
   * @param string[] $untrusted_roles
   *   The new untrusted roles' IDs.
   */
  public function setUntrustedRoles(array $untrusted_roles) {
    $this->config->set('untrusted_roles', $untrusted_roles);
    $this->config->save();
  }

  /**
   * Logs an event.
   *
   * @param \Drupal\security_review\Check $check
   *   The Check the message is about.
   * @param string $message
   *   The message.
   * @param array $context
   *   The context of the message.
   * @param int $level
   *   Severity (RfcLogLevel).
   */
  public function log(Check $check, $message, array $context, $level) {
    if (static::isLogging()) {
      $this->moduleHandler->invokeAll(
        'security_review_log',
        [
          'check' => $check,
          'message' => $message,
          'context' => $context,
          'level' => $level,
        ]
      );
    }
  }

  /**
   * Logs a check result.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The result to log.
   */
  public function logCheckResult(CheckResult $result = NULL) {
    if ($this->isLogging()) {
      if ($result == NULL) {
        $check = $result->check();
        $context = [
          '@check' => $check->getTitle(),
          '@namespace' => $check->getNamespace(),
        ];
        $this->log($check, '@check of @namespace produced a null result', $context, RfcLogLevel::CRITICAL);
        return;
      }

      $check = $result->check();

      // Fallback log message.
      $level = RfcLogLevel::NOTICE;
      $message = '@name check invalid result';

      // Set log message and level according to result.
      switch ($result->result()) {
        case CheckResult::SUCCESS:
          $level = RfcLogLevel::INFO;
          $message = '@name check succeeded';
          break;

        case CheckResult::FAIL:
          $level = RfcLogLevel::ERROR;
          $message = '@name check failed';
          break;

        case CheckResult::WARN:
          $level = RfcLogLevel::WARNING;
          $message = '@name check raised a warning';
          break;

        case CheckResult::INFO:
          $level = RfcLogLevel::INFO;
          $message = '@name check returned info';
          break;
      }

      $context = ['@name' => $check->getTitle()];
      $this->log($check, $message, $context, $level);
    }
  }

  /**
   * Deletes orphaned check data.
   */
  public function cleanStorage() {
    /** @var \Drupal\security_review\Checklist $checklist */
    $checklist = \Drupal::service('security_review.checklist');

    // Get list of check configuration names.
    $orphaned = $this->configFactory->listAll('security_review.check.');

    // Remove items that are used by the checks.
    foreach ($checklist->getChecks() as $check) {
      $key = array_search('security_review.check.' . $check->id(), $orphaned);
      if ($key !== FALSE) {
        unset($orphaned[$key]);
      }
    }

    // Delete orphaned configuration data.
    foreach ($orphaned as $config_name) {
      $config = $this->configFactory->getEditable($config_name);
      $config->delete();
    }
  }

  /**
   * Stores information about the server into the State system.
   */
  public function setServerData() {
    if (!static::isServerPosix() || PHP_SAPI === 'cli') {
      return;
    }
    // Determine web server's uid and groups.
    $uid = posix_getuid();
    $groups = posix_getgroups();

    // Store the data in the State system.
    $this->state->set('security_review.server.uid', $uid);
    $this->state->set('security_review.server.groups', $groups);
  }

  /**
   * Returns whether the server is POSIX.
   *
   * @return bool
   *   Whether the web server is POSIX based.
   */
  public function isServerPosix() {
    return function_exists('posix_getuid');
  }

  /**
   * Returns the UID of the web server.
   *
   * @return int
   *   UID of the web server's user.
   */
  public function getServerUid() {
    return $this->state->get('security_review.server.uid');
  }

  /**
   * Returns the GIDs of the web server.
   *
   * @return int[]
   *   GIDs of the web server's user.
   */
  public function getServerGids() {
    return $this->state->get('security_review.server.groups');
  }

}
