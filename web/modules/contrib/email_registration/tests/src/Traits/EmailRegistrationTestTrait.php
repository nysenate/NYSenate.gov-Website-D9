<?php

namespace Drupal\Tests\email_registration\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;

/**
 * A helper trait for the email_registration module.
 */
trait EmailRegistrationTestTrait {

  /**
   * Overwrites the default "drupalLogin" implementation.
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet('user/login');
    $this->submitForm([
      'mail' => $account->getEmail(),
      'pass' => $account->passRaw,
    ], 'Log in');

    $this->assertLoggedIn($account);
  }

  /**
   * Asserts that a particular user is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check for being logged in.
   */
  protected function assertLoggedIn(AccountInterface $account) {
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
