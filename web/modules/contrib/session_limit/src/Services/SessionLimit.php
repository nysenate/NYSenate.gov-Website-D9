<?php

namespace Drupal\session_limit\Services;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\session_limit\Event\SessionLimitBypassEvent;
use Drupal\session_limit\Event\SessionLimitCollisionEvent;
use Drupal\session_limit\Event\SessionLimitDisconnectEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Config\ConfigFactory;

class SessionLimit implements EventSubscriberInterface {

  const ACTION_ASK = 0;

  const ACTION_DROP_OLDEST = 1;

  const ACTION_PREVENT_NEW = 2;

  const USER_UNLIMITED_SESSIONS = -1;

  /**
   * @return array
   *   Keys are session limit action ids
   *   Values are text descriptions of each action.
   */
  public static function getActions() {
    return [
      SessionLimit::ACTION_ASK => t('Ask user which session to end.'),
      SessionLimit::ACTION_DROP_OLDEST => t('Automatically drop the oldest sessions.'),
      SessionLimit::ACTION_PREVENT_NEW => t('Prevent creating of any new sessions.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest',32];
    $events['session_limit.bypass'][] = ['onSessionLimitBypass'];
    $events['session_limit.collision'][] = ['onSessionCollision'];
    return $events;
  }

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var EventDispatcherInterface
   */

  protected $eventDispatcher;

  /**
   * @var SessionManager
   */
  protected $sessionManager;

  /**
   * @var ModuleHandler
   */
  protected $moduleHandler;

  /**
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SessionLimit constructor.
   *
   * @param Connection $database
   *   The database connection.
   * @param EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   * @param RouteMatchInterface $routeMatch
   *   The Route.
   * @param AccountProxy $currentUser
   *   The current user.
   * @param SessionManager $sessionManager
   *   Session manager.
   * @param ModuleHandler $moduleHandler
   *   Module handler.
   * @param ConfigFactory $configFactory
   *   Config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(Connection $database, EventDispatcherInterface $eventDispatcher, RouteMatchInterface $routeMatch, AccountProxy $currentUser, SessionManager $sessionManager, ModuleHandler $moduleHandler, ConfigFactory $configFactory, MessengerInterface $messenger) {
    $this->routeMatch = $routeMatch;
    $this->database = $database;
    $this->eventDispatcher = $eventDispatcher;
    $this->currentUser = $currentUser;
    $this->sessionManager = $sessionManager;
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * @return RouteMatchInterface
   */
  public function getRouteMatch() {
    return $this->routeMatch;
  }

  /**
   * @return EventDispatcherInterface
   */
  public function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * @return \Drupal\Core\Session\AccountProxyInterface
   */
  protected function getCurrentUser() {
    return $this->currentUser;
  }

  /**
   * Event listener, on executing a Kernel request.
   *
   * Check the users active sessions and invoke a session collision if it is
   * higher than the configured limit.
   */
  public function onKernelRequest() {
    // Show session messages to the user if they have been logged out.
    if(isset($_SESSION['messages'])) {
      $session_messages = $_SESSION['messages'];
      foreach ($session_messages as $severity => $message_object) {
          foreach ($message_object AS $message) {
              \Drupal::messenger()->addMessage($message, $severity);
          }
      }
      // Remove messages from session so that it only displays once.
      unset($_SESSION['messages']);
    }

    /** @var SessionLimitBypassEvent $bypassEvent */
    $bypassEvent = $this
      ->getEventDispatcher()
      ->dispatch(new SessionLimitBypassEvent(), 'session_limit.bypass');

    // Check the result of the event to see if we should bypass.
    if ($bypassEvent->shouldBypass()) {
      return;
    }

    $active_sessions = $this->getUserActiveSessionCount($this->getCurrentUser());
    $max_sessions = $this->getUserMaxSessions($this->getCurrentUser());

    if ($max_sessions > 0 && $active_sessions > $max_sessions) {
      $collisionEvent = new SessionLimitCollisionEvent(session_id(), $this->getCurrentUser(), $active_sessions, $max_sessions);

      $this
        ->getEventDispatcher()
        ->dispatch($collisionEvent, 'session_limit.collision');
    }
    else {
      // force checking this twice as there's a race condition around
      // sessionId creation see issue #1176412.
      // @todo accessing the $_SESSION super global is bad.
      if (!isset($_SESSION['session_limit_checkonce'])) {
        $_SESSION['session_limit_checkonce'] = TRUE;
      }
      else {
        // mark sessionId as verified to bypass this in future.
        $_SESSION['session_limit'] = TRUE;
      }
    }
  }

  /**
   * Event listener, on check for session check bypass.
   *
   * @param SessionLimitBypassEvent $event
   */
  public function onSessionLimitBypass(SessionLimitBypassEvent $event) {

      $admin_bypass_check =  $this->configFactory->get('session_limit.settings')
      ->get('session_limit_admin_inclusion');
      $uid = $admin_bypass_check ? 1 : 2;

    if ($this->getCurrentUser()->id() < $uid) {
      // User 1 and anonymous don't get session checked.
      $event->setBypass(TRUE);
      return;
    }

    if ($this->getMasqueradeIgnore() && \Drupal::service('masquerade')->isMasquerading()) {
      // Masquerading sessions do not count.
      $event->setBypass(TRUE);
      return;
    }

    // @todo accessing the $_SESSION super global is probably bad.
    if (isset($_SESSION['session_limit'])) {
      // Already checked people do not get session checked.
      $event->setBypass(TRUE);
      return;
    }

    $route = $this->getRouteMatch();
    $current_path = $route->getRouteObject()->getPath();

    $bypass_paths = [
      '/session-limit',
      '/user/logout',
    ];

    if (in_array($current_path, $bypass_paths)) {
      // Don't session check on these routes.
      $event->setBypass(TRUE);
      return;
    }
  }

  /**
   * React to a collision event.
   *
   * The user has more sessions than they are allowed. Depending on the
   * configured behaviour of the module we will either drop existing sessions,
   * prevent this new session or ask the user what to do.
   *
   * @param SessionLimitCollisionEvent $event
   */
  public function onSessionCollision(SessionLimitCollisionEvent $event) {
    switch ($this->getCollisionBehaviour()) {
      case self::ACTION_ASK :
        $this->_onSessionCollision__Ask();
        break;

      case self::ACTION_PREVENT_NEW :
        $this->_onSessionCollision__PreventNew($event);
        break;

      case self::ACTION_DROP_OLDEST :
        $this->_onSessionCollision__DropOldest($event);
        break;
    }
  }

  /**
   * React to a session collision by asking the user which session to end.
   */
  protected function _onSessionCollision__Ask() {
    $this->messenger->addMessage(t('You have too many active sessions. Please choose a session to end.'));
    $response = new RedirectResponse(Url::fromRoute('session_limit.limit_form')->toString(), 307, [
      'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
      'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT',
    ]);
    $response->send();
    exit();
  }

  /**
   * React to a session collision by preventing new sessions.
   *
   * @param SessionLimitCollisionEvent $event
   *   The session collision event.
   */
  protected function _onSessionCollision__PreventNew(SessionLimitCollisionEvent $event) {
    /** @var SessionLimitDisconnectEvent $disconnectEvent */
    $disconnectEvent = $this
      ->getEventDispatcher()
      ->dispatch(new SessionLimitDisconnectEvent($event->getSessionId(), $event, $this->getMessage($event->getAccount())), 'session_limit.disconnect');

    if (!$disconnectEvent->shouldPreventDisconnect()) {
      $this->sessionActiveDisconnect($disconnectEvent->getMessage());
    }
  }

  /**
   * React to a session collision by dropping older sessions.
   *
   * @param SessionLimitCollisionEvent $event
   *   The session collision event.
   */
  protected function _onSessionCollision__DropOldest(SessionLimitCollisionEvent $event) {
    // Get the number of sessions that should be removed.
    // @todo replace the straight db query with a select.
    $limit = $this->database->query("SELECT COUNT(DISTINCT(sid)) - :max_sessions FROM {sessions} WHERE uid = :uid", array(
      ':max_sessions' => $event->getUserMaxSessions(),
      ':uid' => $event->getAccount()->id(),
    ))->fetchField();

    if ($limit > 0) {
      // Secure sessionId ids are separate rows in the database, but we don't
      // want to kick the user off there http sessionId and not there https
      // sessionId or vice versa. This is why this query is DISTINCT.
      $result = $this->database->select('sessions', 's')
        ->distinct()
        ->fields('s', array('sid', 'timestamp'))
        ->condition('s.uid', $event->getAccount()->id())
        ->orderBy('timestamp', 'ASC')
        ->range(0, $limit)
        ->execute();

      foreach ($result as $session) {
        /** @var SessionLimitDisconnectEvent $disconnectEvent */
        $disconnectEvent = $this
          ->getEventDispatcher()
          ->dispatch(new SessionLimitDisconnectEvent($event->getSessionId(), $event, $this->getMessage($event->getAccount())), 'session_limit.disconnect');

        if (!$disconnectEvent->shouldPreventDisconnect()) {
          $this->sessionDisconnect($session->sid, $disconnectEvent->getMessage());
        }
      }
    }
  }

  /**
   * Disconnect a sessionId.
   *
   * @param string $sessionId
   *   The session being disconnected
   * @param string $message
   *   The logout message which must be already translated by this point
   */
  public function sessionDisconnect($sessionId, $message) {
    $serialized_message = '';

    if ($this->hasMessageSeverity() && !empty($message)) {
      $serialized_message = 'messages|' . serialize([
          $this->getMessageSeverity() => [$message],
        ]);
    }

    $this->database->update('sessions')
      ->fields([
        'uid' => 0,
        'session' => $serialized_message,
      ])
      ->condition('sid', $sessionId)
      ->execute();

    // @todo add a watchdog log entry.
  }

  /**
   * Disconnect the active session.
   *
   * This is called when the user is prevented from logging in due to an
   * existing open session.
   *
   * @param string $message
   */
  public function sessionActiveDisconnect($message) {
    $this->messenger->addMessage($message, $this->getMessageSeverity());
    $this->moduleHandler->invokeAll('user_logout', array($this->currentUser));
    $this->sessionManager->destroy();
    $this->currentUser->setAccount(new AnonymousUserSession());
  }

  /**
   * Get the number of active sessions for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check on.
   *
   * @return int
   *   The total number of active sessions for the given user
   */
  public function getUserActiveSessionCount(AccountInterface $account) {
    $query = $this->database->select('sessions', 's')
      // Use distinct so that HTTP and HTTPS sessions
      // are considered a single sessionId.
      ->distinct()
      ->fields('s', ['sid'])
      ->condition('s.uid', $account->id());

    if ($this->getMasqueradeIgnore()) {
      // Masquerading sessions do not count.
      $like = '%' . $this->database->escapeLike('masquerading') . '%';
      $query->condition('s.session', $like, 'NOT LIKE');
    }

    /** @var \Drupal\Core\Database\Query\Select $query */
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Get a list of active sessions for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check on.
   *
   * @return array
   *   A list of session objects for the user.
   */
  public function getUserActiveSessions(AccountInterface $account) {
    $query = $this->database->select('sessions', 's')
      ->fields('s', ['uid', 'sid', 'hostname', 'timestamp'])
      ->condition('s.uid', $account->id());

    if ($this->getMasqueradeIgnore()) {
      // Masquerading sessions do not count.
      $like = '%' . $this->database->escapeLike('masquerading') . '%';
      $query->condition('s.session', $like, 'NOT LIKE');
    }

    /** @var \Drupal\Core\Database\Query\Select $query */
    return $query->execute()->fetchAll();
  }

  /**
   * Get the maximum sessions allowed for a specific user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return int
   *   The number of allowed sessions. A value less than 1 means unlimited.
   */
  public function getUserMaxSessions(AccountInterface $account) {
    $limit = $this->configFactory->get('session_limit.settings')
      ->get('session_limit_max');
    $role_limits = $this->configFactory->get('session_limit.settings')
      ->get('session_limit_roles');

    foreach ($account->getRoles() as $rid) {
      if (!empty($role_limits[$rid])) {
        if ($role_limits[$rid] == self::USER_UNLIMITED_SESSIONS) {
          // If they have an unlimited role then just return the unlimited value;
          return self::USER_UNLIMITED_SESSIONS;
        }

        // Otherwise, the user gets the largest limit available.
        $limit = max($limit, $role_limits[$rid]);
      }
    }

    // @todo reinstate per user limits.

    return $limit;
  }

  /**
   * @return int
   *   Will return one of the constants provided by getActions().
   */
  public function getCollisionBehaviour() {
    return $this->configFactory->get('session_limit.settings')
      ->get('session_limit_behaviour');
  }

  /**
   * @return bool
   *   Should we ignore masqueraded sessions?
   */
  public function getMasqueradeIgnore() {
    $masqueradeModuleExists = $this->moduleHandler->moduleExists('masquerade');
    if (!$masqueradeModuleExists) {
      return FALSE;
    }

    return $this->configFactory->get('session_limit.settings')
      ->get('session_limit_masquerade_ignore');
  }

  /**
   * return @bool
   */
  public function hasMessageSeverity() {
    $severity = $this->getMessageSeverity();
    return !empty($severity) && in_array($severity, [
      'error',
      'warning',
      'status'
    ]);
  }

  /**
   * Get the severity of the logout message to the user.
   *
   * @return string
   */
  public function getMessageSeverity() {
    return $this->configFactory->get('session_limit.settings')
      ->get('session_limit_logged_out_message_severity');
  }

  /**
   * Get the logged out message for the given user.
   *
   * @param AccountInterface $account
   * @return string
   */
  public function getMessage(AccountInterface $account) {
    return t('You have been automatically logged out. Someone else has logged in with your username and password and the maximum number of @number simultaneous session(s) was exceeded. This may indicate that your account has been compromised or that account sharing is not allowed on this site. Please contact the site administrator if you suspect your account has been compromised.', [
      '@number' => $this->getUserMaxSessions($account),
    ]);
  }
}
