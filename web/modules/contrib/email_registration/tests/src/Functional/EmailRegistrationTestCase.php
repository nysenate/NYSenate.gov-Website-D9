<?php

namespace Drupal\Tests\email_registration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the email registration module.
 *
 * @group email_registration
 */
class EmailRegistrationTestCase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['email_registration'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test various behaviors for anonymous users.
   */
  public function testRegistration() {
    $user_config = $this->container->get('config.factory')->getEditable('user.settings');
    $email_registration_config = $this->container->get('config.factory')->getEditable('email_registration.settings');
    $user_config
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();
    // Try to register a user.
    $name = $this->randomMachineName();
    $pass = $this->randomString(10);
    $register = [
      'mail' => $name . '@example.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ];
    $this->drupalPostForm('/user/register', $register, 'Create new account');
    $this->drupalLogout();

    $login = [
      'name' => $name . '@example.com',
      'pass' => $pass,
    ];
    $this->drupalPostForm('user/login', $login, 'Log in');

    // Really basic confirmation that the user was created and logged in.
    $this->assertSession()->responseContains('<title>' . $name . ' | Drupal</title>');

    // Now try the immediate login.
    $this->drupalLogout();

    // Try to login with just username, should fail by default.
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Enter your email address.');
    $this->assertSession()->responseContains('Email');
    $this->assertSession()->responseNotContains('Email or username');
    $login = [
      'name' => $name,
      'pass' => $pass,
    ];
    $this->drupalPostForm('user/login', $login, 'Log in');
    // When login_with_username is false, a user cannot login with just their
    // username.
    $this->assertSession()->responseContains('Unrecognized email address or password.');

    // Set login_with_username to TRUE and try to login with just username.
    $email_registration_config->set('login_with_username', TRUE)->save();
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Enter your email address or username.');
    $this->assertSession()->responseContains('Email or username');
    $this->drupalPostForm('user/login', $login, 'Log in');
    // When login_with_username is true, a user can login with just their
    // username.
    $this->assertSession()->responseContains('<title>' . $name . ' | Drupal</title>');
    $this->drupalLogout();

    $user_config
      ->set('verify_mail', FALSE)
      ->save();
    $name = $this->randomMachineName();
    $pass = $this->randomString(10);
    $register = [
      'mail' => $name . '@example.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ];
    $this->drupalPostForm('/user/register', $register, 'Create new account');
    // User properly created, immediately logged in.
    $this->assertSession()->responseContains('Registration successful. You are now logged in.');

    // Test email_registration_unique_username().
    $this->drupalLogout();
    $user_config
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();
    $name = $this->randomMachineName(32);
    $pass = $this->randomString(10);

    $this->createUser([], $name);
    $next_unique_name = email_registration_unique_username($name);

    $register = [
      'mail' => $name . '@example2.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ];
    $this->drupalPostForm('/user/register', $register, 'Create new account');
    $account = user_load_by_mail($register['mail']);
    $this->assertSame($next_unique_name, $account->getAccountName());
    $this->drupalLogout();

    // Check if custom username stays the same when user is edited.
    $user = $this->createUser();
    $name = $user->label();
    $this->drupalLogin($user);
    $this->drupalPostForm('/user/' . $user->id() . '/edit', [], 'Save');
    $this->assertEqual($name, User::load($user->id())->label(), 'Username should not change after empty edit.');
    $this->drupalLogout();
    $this->drupalLogin($user);
    $this->assertSame($next_unique_name, $account->getAccountName());
  }

  /**
   * Test the "change own username" permission and user edit save.
   */
  public function testUsernamePermissions() {
    // Set login_with_username to TRUE for $this->>drupalLogin.
    $this->container->get('config.factory')
      ->getEditable('email_registration.settings')
      ->set('login_with_username', TRUE)
      ->save(TRUE);

    $user = $this->createUser(['change own username']);
    $this->drupalLogin($user);
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertSession()->fieldExists('edit-name');

    $this->drupalLogout();

    $user = $this->createUser();
    $username = $user->getAccountName();

    $this->drupalLogin($user);
    $this->drupalGet('user/' . $user->id() . '/edit');
    // Test that the field is set to type=value.
    $this->assertSession()->fieldNotExists('edit-name');
    $this->assertSession()->pageTextContains($username);
    // Make sure the email isn't changed on save.
    $this->drupalPostForm('user/' . $user->id() . '/edit', [], 'Save');
    $this->assertSession()->pageTextContains($username);
  }

}
