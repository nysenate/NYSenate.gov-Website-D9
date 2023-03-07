<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  public function spin ($lambda, $wait = 60)
  {
    for ($i = 0; $i < $wait; $i++)
    {
      try {
        if ($lambda($this)) {
          return true;
        }
      } catch (Exception $e) {
        // do nothing
      }

      sleep(1);
    }

    $backtrace = debug_backtrace();

    throw new Exception(
      "Timeout thrown by " . $backtrace[1]['class'] . "::" . $backtrace[1]['function'] . "()\n" .
      $backtrace[1]['file'] . ", line " . $backtrace[1]['line']
    );
  }

  /**
   * Waits to see the login form.
   *
   * @Given I see the login form
   */
  public function iSeeLoginForm($selector) {
    $this->spin(function($context) {
      return ($context->getSession()->getPage()->findById('user-login-form')->isVisible());
    });
  }
}
