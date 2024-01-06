<?php

namespace Drupal\nys_registration\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\user\Controller\UserController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom User Controller.
 */
class NysUserController extends UserController {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user'),
      $container->get('flood')
    );

    $instance->tempStoreFactory = $container->get('tempstore.private');

    return $instance;
  }

  /**
   * Validates user, hash, and timestamp; logs the user in if correct.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the user edit form if the information is correct.
   *   If the information is incorrect redirects to 'user.pass' route with a
   *   message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  public function resetPassLogin($uid, $timestamp, $hash, Request $request) {
    /**
     * @var \Drupal\user\UserInterface $user
     */
    $user = $this->userStorage->load($uid);
    if ($redirect = $this->determineErrorRedirect($user, $timestamp, $hash)) {
      return $redirect;
    }

    $flood_config = $this->config('user.flood');
    if ($flood_config->get('uid_only')) {
      $identifier = $user->id();
    }
    else {
      $identifier = $user->id() . '-' . $request->getClientIP();
    }

    $this->flood->clear('user.failed_login_user', $identifier);
    $this->flood->clear('user.http_login', $identifier);

    user_login_finalize($user);
    $this->logger->notice(
      'User %name used one-time login link at time %timestamp.',
      [
        '%name' => $user->getDisplayName(),
        '%timestamp' => $timestamp,
      ]
    );
    $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please set your password.'));
    // Let the user's password be changed without the current password
    // check.
    $token = Crypt::randomBytesBase64(55);
    $request->getSession()->set('pass_reset_' . $user->id(), $token);
    // Clear any flood events for this user.
    $this->flood->clear('user.password_request_user', $uid);

    // Bypass email TFA during password reset workflow.
    $this->tempStoreFactory->get('email_tfa')->set('email_tfa_user_verify', 1);

    return $this->redirect(
      'nys_registration.password_reset',
      ['user' => $user->id()],
      [
        'query' => ['pass-reset-token' => $token],
        'absolute' => TRUE,
      ]
    );
  }

}
