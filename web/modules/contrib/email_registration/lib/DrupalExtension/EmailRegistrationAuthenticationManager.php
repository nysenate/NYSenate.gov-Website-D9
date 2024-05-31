<?php

namespace Drupal\email_registration\Component\DrupalExtension;

use Drupal\DrupalExtension\Manager\DrupalAuthenticationManager;

/**
 * The Email registration authentication manager service.
 */
class EmailRegistrationAuthenticationManager extends DrupalAuthenticationManager {

  /**
   * {@inheritdoc}
   */
  public function logIn(\stdClass $user) {
    // Check if the email field for the user is present.
    if (empty($user->mail)) {
      if (isset($user->role)) {
        throw new \Exception(sprintf("Unable to log in user '%s' with role '%s' without email address", $user->name, $user->role));
      }
      else {
        throw new \Exception(sprintf("Unable to log in user '%s' without email address", $user->name));
      }
    }

    // Check if we are already logged in.
    if ($this->loggedIn()) {
      $this->logout();
    }

    $this->getSession()->visit($this->locatePath('/user'));
    $element = $this->getSession()->getPage();
    $element->fillField('edit-name', $user->mail);
    $element->fillField('edit-pass', $user->pass);
    $submit = $element->findButton('edit-submit');

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      if (isset($user->role)) {
        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s' with role '%s'", $user->name, $user->role));
      }
      else {
        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s'", $user->name));
      }
    }

    $this->userManager->setCurrentUser($user);
  }

}
