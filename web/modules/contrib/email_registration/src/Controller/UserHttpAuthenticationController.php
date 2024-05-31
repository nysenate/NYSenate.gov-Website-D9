<?php

declare(strict_types=1);

namespace Drupal\email_registration\Controller;

use Drupal\user\Controller\UserAuthenticationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides controllers for login with mail and password via HTTP requests.
 */
class UserHttpAuthenticationController extends UserAuthenticationController {

  /**
   * {@inheritdoc}
   */
  public function login(Request $request) {
    $format = $this->getRequestFormat($request);
    $content = $request->getContent();
    $credentials = $this->serializer->decode($content, $format);

    // If a name is provided, fallback to
    // \Drupal\user\Controller\UserAuthenticationController::login.
    if (isset($credentials['name'])) {
      return parent::login($request);
    }

    if (!isset($credentials['mail']) && !isset($credentials['pass'])) {
      throw new BadRequestHttpException('Missing credentials.');
    }

    if (!isset($credentials['mail'])) {
      throw new BadRequestHttpException('Missing credentials.mail.');
    }
    if (!isset($credentials['pass'])) {
      throw new BadRequestHttpException('Missing credentials.pass.');
    }

    // Set default.
    $name = '';

    // If an email is provided, find the associated name.
    if ($user = user_load_by_mail($credentials['mail'])) {
      $name = $user->getAccountName();

      $this->floodControl($request, $name);

      if ($this->userIsBlocked($name)) {
        throw new BadRequestHttpException('The user has not been activated or is blocked.');
      }

      if ($uid = $this->userAuth->authenticate($name, $credentials['pass'])) {
        $this->userFloodControl->clear('user.http_login', $this->getLoginFloodIdentifier($request, $name));
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->userStorage->load($uid);
        $this->userLoginFinalize($user);

        // Send basic metadata about the logged in user.
        $response_data = [];
        if ($user->get('uid')->access('view', $user)) {
          $response_data['current_user']['uid'] = $user->id();
        }
        if ($user->get('roles')->access('view', $user)) {
          $response_data['current_user']['roles'] = $user->getRoles();
        }
        if ($user->get('mail')->access('view', $user)) {
          $response_data['current_user']['mail'] = $user->getEmail();
        }
        $response_data['csrf_token'] = $this->csrfToken->get('rest');

        $logout_route = $this->routeProvider->getRouteByName('user.logout.http');
        // Trim '/' off path to match \Drupal\Core\Access\CsrfAccessCheck.
        $logout_path = ltrim($logout_route->getPath(), '/');
        $response_data['logout_token'] = $this->csrfToken->get($logout_path);

        $encoded_response_data = $this->serializer->encode($response_data, $format);
        return new Response($encoded_response_data);
      }
    }

    $flood_config = $this->config('user.flood');
    if ($identifier = $this->getLoginFloodIdentifier($request, $name)) {
      $this->userFloodControl->register('user.http_login', $flood_config->get('user_window'), $identifier);
    }

    // Always register an IP-based failed login event.
    $this->userFloodControl->register('user.failed_login_ip', $flood_config->get('ip_window'));
    throw new BadRequestHttpException('Sorry, unrecognized email or password.');
  }

}
