<?php

namespace Drupal\password_policy\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Enforces password reset functionality.
 */
class PasswordPolicyEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * The Masquerade service if it exists, or NULL.
   *
   * @var \Drupal\masquerade\Masquerade|null
   */
  protected $masquerade;

  /**
   * PasswordPolicyEventSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The currently logged in user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\masquerade\Masquerade|null $masquerade
   *   The Masquerade service if it exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, RequestStack $requestStack, $masquerade = NULL) {
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
    $this->request = $requestStack->getCurrentRequest();
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->masquerade = $masquerade;
  }

  /**
   * Event callback to look for users expired password.
   */
  public function checkForUserPasswordExpiration(RequestEvent $event) {
    $route_name = $this->request->attributes->get(RouteObjectInterface::ROUTE_NAME);
    $ignore_route = in_array($route_name, [
      'system.403',
      'entity.user.edit_form',
      'system.ajax',
      'user.logout',
      'admin_toolbar_tools.flush',
      'user.pass',
    ]);

    // Ignore route for jsonapi calls.
    if (strpos($route_name, 'jsonapi') !== FALSE) {
      return;
    }

    // There needs to be an explicit check for non-anonymous or else
    // this will be tripped and a forced redirect will occur.
    if ($this->currentUser->isAuthenticated()) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->load($this->currentUser->id());

      $is_ajax = $this->request->headers->get('X_REQUESTED_WITH') === 'XMLHttpRequest';

      $user_expired = FALSE;
      if ($user && $user->hasField('field_password_expiration') && $user->get('field_password_expiration')->get(0)) {
        $user_expired = $user->get('field_password_expiration')
          ->get(0)
          ->getValue();
        $user_expired = $user_expired['value'];
      }

      // Bail early if the current user is masquerading. Masquerade should not
      // force a password reset.
      if (isset($this->masquerade)) {
        if ($this->masquerade->isMasquerading()) {
          return;
        }
      }

      // Save the token passed in the URL when the user access the site via
      // one time login.
      $session = $this->request->getSession();
      $token = $this->request->query->get('pass-reset-token');
      if (isset($token)) {
        $session->set('pass-reset-token-password-policy', $token);
      }

      // Check if the hash saved in the session is the same as the one saved in
      // UserController::resetPassLogin() so the user is not redirected when
      // access the site via one time login. This will prevent the user to
      // populate manually the parameter.
      $token = $session->get('pass-reset-token-password-policy');
      $pass_reset_token = $session->get('pass_reset_' . $user->id());
      $is_hash_equal = FALSE;
      if (isset($token) && isset($pass_reset_token)) {
        $is_hash_equal = hash_equals($pass_reset_token, $token);
      }

      // @todo Consider excluding admins here.
      if ($user_expired && !$ignore_route && !$is_ajax && !$is_hash_equal) {
        $url = new Url('entity.user.edit_form', ['user' => $user->id()]);
        $url = $url->setAbsolute()->toString();
        $event->setResponse(new RedirectResponse($url));
        $this->messenger->addError(
          $this->t('Your password has expired, please update it')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // @todo Evaluate if there is a better place to add this check.
    $events[KernelEvents::REQUEST][] = ['checkForUserPasswordExpiration', 28];
    return $events;
  }

}
