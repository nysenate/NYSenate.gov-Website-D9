<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Core\Form\FormState;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test that emails are properly sent when configured.
 *
 * @group login_security
 */
class LoginSecurityEmailTest extends LoginSecurityTestBase {
  use AssertMailTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'login_security'];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create first user.
    $this->drupalCreateUser();

    // Create second user.
    $this->account = $this->drupalCreateUser();

    $this->drupalLoginLite($this->account);

    // Setup emails to be sent.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_blocked_notification_emails', 'test@test.com')
      ->set('login_activity_notification_emails', 'test@test.com')
      ->save();
  }

  /**
   * Test that email is sent when users are blocked.
   */
  public function testBlockedEmail() {
    $variables = ['@uid' => $this->account->id()];
    $form_state = new FormState();
    login_user_block_user_name($variables, $form_state);
    $this->assertMail('to', 'test@test.com', 'Mail sent when a user is blocked.');
  }

  /**
   * Test that email is sent when activity exceeds configured threshold.
   */
  public function testActivityThresholdEmail() {
    // @todo.
  }

}
