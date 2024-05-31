<?php

declare(strict_types=1);

namespace Drupal\Tests\email_registration\Functional;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the email registration module.
 *
 * @group email_registration
 */
class EmailRegistrationFunctionalTest extends EmailRegistrationFunctionalTestBase {

  /**
   * Tests if installing the module, won't break the site.
   */
  public function testInstallation() {
    $session = $this->assertSession();
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    $this->drupalLogin($this->adminUser);
    // Go to uninstallation page an uninstall email_registration_username:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-email-registration');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
    // Retest the frontpage:
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Test the error message when a blocked user tries to login.
   */
  public function testBlockedUserLogin() {
    $user_config = $this->container->get('config.factory')->getEditable('user.settings');
    $email_registration_config = $this->container->get('config.factory')->getEditable('email_registration.settings');
    $user_config
      ->set('verify_mail', FALSE)
      ->save();
    $user = $this->createUser([], $this->randomMachineName(), FALSE, [
      'mail' => $this->randomMachineName() . '@example.com',
      'pass' => 'test',
      'status' => 0,
    ]);
    $email_registration_config->set('login_with_username', FALSE)->save();
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Enter your email address.');
    $this->assertSession()->responseContains('Enter the password that accompanies your email address.');
    $this->submitForm([
      'name' => $user->get('mail')->value,
      'pass' => $user->passRaw,
    ], 'Log in');
    $this->assertSession()->pageTextContains('The account with email address ' . $user->get('mail')->value . ' has not been activated or is blocked.');
  }

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
    $this->drupalGet('/user/register');
    $this->submitForm($register, 'Create new account');
    $this->drupalLogout();

    $login = [
      'name' => $name . '@example.com',
      'pass' => $pass,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($login, 'Log in');

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
    $this->submitForm($login, 'Log in');
    // When login_with_username is false, a user cannot login with just their
    // username.
    $this->assertSession()->responseContains('Unrecognized email address or password.');

    // Set login_with_username to TRUE and try to login with just username.
    $email_registration_config->set('login_with_username', TRUE)->save();
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Enter your email address or username.');
    $this->assertSession()->responseContains('Email address or username');
    $this->submitForm($login, 'Log in');
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
    $this->drupalGet('/user/register');
    $this->submitForm($register, 'Create new account');
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
    $this->drupalGet('/user/register');
    $this->submitForm($register, 'Create new account');
    $account = user_load_by_mail($register['mail']);
    $this->assertSame($next_unique_name, $account->getAccountName());
    $this->drupalLogout();

    // Check if custom username stays the same when user is edited.
    $user = $this->createUser();
    $name = $user->label();
    $this->drupalLogin($user);
    $this->drupalGet('/user/' . $user->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertEquals($name, User::load($user->id())->label(), 'Username should not change after empty edit.');
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
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($username);
  }

  /**
   * Tests the options to allow the username on registration.
   */
  public function testAllowUsernameRegistration() {

    $name = strtolower($this->randomMachineName());
    $pass = $this->randomString();

    // Login as admin user:
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/people/create');
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $session->fieldExists('edit-mail');
    // Admin users should see the Username field:
    $session->fieldExists('edit-name');
    // The modified username description should also exist:
    $session->elementTextEquals('css', '#edit-name--description', "Leave empty to generate the username from the email address.Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign.");
    $session->fieldExists('Password');
    $session->fieldExists('Confirm password');

    // Omit the username.
    $page->fillField('edit-mail', "$name@example.com");
    $page->fillField('Password', $pass);
    $page->fillField('Confirm password', $pass);
    $page->pressButton('Create new account');

    $session->pageTextContains("Created a new user account for $name. No email has been sent.");

    // Log out.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // Check that the username is not present on the login form.
    $session->fieldNotExists('Username');

    $page->fillField('Email address', "$name@example.com");
    $page->fillField('Password', $pass);
    $page->pressButton('Log in');

    // Check that the user is logged in.
    $session->pageTextContains($name);

    \Drupal::configFactory()->getEditable('email_registration.settings')
      ->set('login_with_username', TRUE)
      ->save();

    // Log out.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // Login with username:
    $page->fillField('Email address or username', $name);
    $page->fillField('Password', $pass);
    $page->pressButton('Log in');

    // Check that the user is logged in.
    $session->pageTextContains($name);

    // Log out.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // Login with email.
    $page->fillField('Email address or username', "$name@example.com");
    $page->fillField('Password', $pass);
    $page->pressButton('Log in');

    // Check that the user is logged in.
    $session->pageTextContains($name);
  }

  /**
   * Tests the "email_registration_update_username" action.
   */
  public function testBatchAction() {
    // Rename both the "user" and "adminUser":
    $this->user->setUserName('testUser1');
    $this->user->setEmail('testA@mail.com');
    $this->user->save();
    $this->adminUser->setUserName('testUser2');
    $this->adminUser->setEmail('testB@mail.com');
    $this->adminUser->save();

    // Execute our action on the users:
    $updateUsernameAction = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->load('email_registration_update_username');
    $updateUsernameAction->execute([$this->user, $this->adminUser]);

    // The active users username should be a stripped variant of their
    // email-address:
    $this->assertSame('testA', $this->user->getAccountName());
    $this->assertSame('testB', $this->adminUser->getAccountName());
  }

  /**
   * Tests the "email_registration_update_username" action.
   */
  public function testBatchActionAlreadyExistingName() {
    // Rename both the "user" and "adminUser":
    $this->user->setUserName('testUser1');
    $this->user->save();
    $this->adminUser->setUserName('testUser2');
    $this->adminUser->save();
    // Execute our action on the users:
    $updateUsernameAction = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->load('email_registration_update_username');
    $updateUsernameAction->execute([$this->user, $this->adminUser]);
    // The active "user" username should be a stripped variant of their
    // email-address:
    $this->assertSame('user', $this->user->getAccountName());
    // Since there is already an 'admin' user through BrowserTestBase,
    // this user will be named 'admin_1':
    $this->assertSame('admin_1', $this->adminUser->getAccountName());
  }

  /**
   * Tests user editing own credentials won't change their username.
   *
   * Makes sure, the email_registration naming conventions won't magically
   * overwrite the username, when the user is edited.
   */
  public function testUserEditOwnCredentialsDoesNotChangeUsername() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $user = $this->createUser(['change own username']);
    $this->drupalLogin($user);
    $this->drupalGet('user/' . $user->id() . '/edit');
    // First let's see if changing the name won't reset the username:
    $page->fillField('edit-name', 'Changed Username');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The changes have been saved.');
    $session->elementTextEquals('css', 'h1', 'Changed Username');
    $session->elementAttributeContains('css', '#edit-name', 'value', 'Changed Username');
    // Now do the same with the email-address:
    $page->fillField('edit-current-pass', $user->passRaw);
    $page->fillField('edit-mail', 'myVeryNewMail@address.com');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The changes have been saved.');
    $session->elementTextEquals('css', 'h1', 'Changed Username');
    $session->elementAttributeContains('css', '#edit-name', 'value', 'Changed Username');
  }

  /**
   * Tests admin editing user credentials won't change their username.
   *
   * Makes sure, the email_registration naming conventions won't magically
   * overwrite the username, when the user is edited.
   */
  public function testAdminEditUserCredentialsDoesNotChangeUsername() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $this->user->id() . '/edit');
    // First let's see if changing the name won't reset the username:
    $page->fillField('edit-name', 'Changed Username');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The changes have been saved.');
    $session->elementTextEquals('css', 'h1', 'Changed Username');
    $session->elementAttributeContains('css', '#edit-name', 'value', 'Changed Username');
    // Now do the same with the email-address:
    $page->fillField('edit-mail', 'myVeryNewMail@address.com');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The changes have been saved.');
    $session->elementTextEquals('css', 'h1', 'Changed Username');
    $session->elementAttributeContains('css', '#edit-name', 'value', 'Changed Username');
  }

  /**
   * Test programmatically editing user credentials won't change their username.
   *
   * Makes sure, the email_registration naming conventions won't magically
   * overwrite the username, when the user is edited.
   */
  public function testProgrammaticallyEditingUserDoesNotChangeUsername() {
    // First let's see if changing the name won't reset the username:
    $this->user->setUsername('Changed Username')->save();
    $this->assertEquals('Changed Username', $this->user->getAccountName());
    // Now do the same with the email-address:
    $this->user->setEmail('myVeryNewMail@address.com')->save();
    $this->assertEquals('myVeryNewMail@address.com', $this->user->getEmail());
    $this->assertEquals('Changed Username', $this->user->getAccountName());
  }

  /**
   * Tests, that logging in won't magically change the users username.
   */
  public function testLoginDoesNotChangeUsername() {
    $user = $this->drupalCreateUser([], 'Initial Username', FALSE, ['mail' => 'completelyDifferent@mail.com']);
    $this->drupalLogin($user);
    $this->assertEquals('Initial Username', $user->getAccountName());
    $this->assertEquals('completelyDifferent@mail.com', $user->getEmail());
  }

}
