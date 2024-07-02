<?php

declare(strict_types=1);

namespace Drupal\Tests\email_registration_username\Functional;

use Drupal\Tests\email_registration\Functional\EmailRegistrationFunctionalTestBase;
use Drupal\Tests\email_registration\Traits\EmailRegistrationTestTrait;

/**
 * This class provides methods specifically for testing something.
 *
 * @group email_registration_username
 */
class EmailRegistrationUsernameFunctionalTest extends EmailRegistrationFunctionalTestBase {
  use EmailRegistrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'token',
    'email_registration_username',
  ];

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
    $page->checkField('edit-uninstall-email-registration-username');
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
   * Tests if the account registration works as expected.
   */
  public function testAccountRegistration() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $testUserMail = 'test@test.com';

    // Register a new user via UI:
    $this->drupalGet('/user/register');
    $session->statusCodeEquals(200);
    $page->fillField('edit-mail', $testUserMail);
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('A welcome message with further instructions has been sent to your email address.');

    // Check, that the new users account name is equal to their mail:
    $testUser = user_load_by_mail($testUserMail);
    $this->assertSame($testUserMail, $testUser->getEmail());
    $this->assertSame($testUserMail, $testUser->getAccountName());
  }

  /**
   * Tests, that the mail and username are in sync on already synced account.
   */
  public function testMailAndUsernameSyncedOnSyncedAccount() {
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    // Change the mail and see if the username will be changed as well:
    $testUser->setEmail('changed@changed.com')->save();
    $this->assertSame($testUser->getEmail(), $testUser->getAccountName());
  }

  /**
   * Tests, that mail and username don't sync, if they were not synced before.
   */
  public function testMailAndUsernameNotSyncedOnUnsyncedAccount() {
    // Create a test user with an unsynced mail and username:
    $testUser = $this->drupalCreateUser([], 'test', FALSE, [
      'mail' => 'test@test.com',
    ]);
    // Change the mail and see if the username stays the same:
    $testUser->setEmail('changed@changed.com')->save();
    $this->assertSame('test', $testUser->getAccountName());
  }

  /**
   * Tests the "email_registration_update_username" action.
   */
  public function testBatchAction() {
    // Check the active users username:
    $this->assertSame('user', $this->user->getAccountName());
    $this->assertSame('adminUser', $this->adminUser->getAccountName());

    $updateUsernameAction = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->load('email_registration_update_username');
    $updateUsernameAction->execute([$this->user, $this->adminUser]);

    // Check the active users username is now their email address:
    $this->assertSame($this->user->getEmail(), $this->user->getAccountName());
    $this->assertSame($this->adminUser->getEmail(), $this->adminUser->getAccountName());
  }

  /**
   * Tests if the override can be disabled.
   */
  public function testDisplayOverrideDisabled() {
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'disabled')->save();
    // Login as a user without the 'view user email addresses' permission:
    $this->drupalLogin($this->user);

    // Override shouldn't happen:
    $this->assertSame('test@test.com', $testUser->getDisplayName());
  }

  /**
   * Tests the override default.
   */
  public function testDisplayOverrideDefault() {
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'email_registration')->save();
    // Login as a user without the 'view user email addresses' permission:
    $this->drupalLogin($this->user);

    $this->assertNotSame('test@test.com', $testUser->getDisplayName());
    // The default/fallback logic should obfuscate "test@test.com" to "test":
    $this->assertSame('test', $testUser->getDisplayName());
  }

  /**
   * Test override with a custom static override value.
   */
  public function testCustomDisplayOverrideStaticValue() {
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    // Login as a user without the 'view user email addresses' permission:
    $this->drupalLogin($this->user);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'custom')->save();
    $this->config('email_registration_username.settings')->set('username_display_custom', 'OBFUSCATED')->save();

    $this->assertNotSame('test@test.com', $testUser->getDisplayName());
    $this->assertSame('OBFUSCATED', $testUser->getDisplayName());
  }

  /**
   * Tests override with a static override value set through the UI.
   */
  public function testDisplayOverrideStaticValueViaUi() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    // Login as a user with admin permissions:
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/people/accounts');
    $session->statusCodeEquals(200);
    $page->fillField('edit-username-display-override-mode-custom', 'custom');
    $page->fillField('edit-username-display-custom', 'OBFUSCATED');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();

    // The anonymous user should see all users obfuscated:
    $this->assertNotSame('test@test.com', $testUser->getDisplayName());
    $this->assertSame('OBFUSCATED', $testUser->getDisplayName());

    // An authenticated user without the "view user email addresses", should
    // also see it obfuscated:
    $this->drupalLogin($this->user);
    $this->assertNotSame('test@test.com', $testUser->getDisplayName());
    $this->assertSame('OBFUSCATED', $testUser->getDisplayName());
  }

  /**
   * Tests override with a token.
   */
  public function testDisplayOverrideTokenSiteName() {
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    // Login as a user without the 'view user email addresses' permission:
    $this->drupalLogin($this->user);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'custom')->save();
    $this->config('email_registration_username.settings')->set('username_display_custom', '[site:name]')->save();

    $this->assertSame('Drupal', $testUser->getDisplayName());
  }

  /**
   * Tests override with a different token.
   */
  public function testDisplayOverrideTokenUserMail() {
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    // Login as a user without the 'view user email addresses' permission:
    $this->drupalLogin($this->user);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'custom')->save();
    $this->config('email_registration_username.settings')->set('username_display_custom', '[user:mail]')->save();

    $this->assertSame('test@test.com', $testUser->getDisplayName());
  }

  /**
   * Test the override on nodes.
   */
  public function testDisplayOverrideOnNode() {
    $session = $this->assertSession();
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);
    \Drupal::service('module_installer')->install(['node']);

    $this->createContentType(['type' => 'article']);
    $node = $this->createNode([
      'type' => 'article',
      'id' => 1,
      'title' => 'test123',
    ]);
    $node->setOwner($testUser);
    $node->save();

    // Login as a user without the 'view user email addresses' permission:
    $this->drupalLogin($this->user);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'email_registration')->save();

    // See if the author is obfuscated:
    $this->drupalGet('/node/' . $node->id());
    $session->pageTextContains('Submitted by test on');
    $session->pageTextNotContains('Submitted by test@test.com on');

    $this->drupalLogout();
    // Login as a user WITH the 'view user email addresses' permission:
    $mailUser = $this->drupalCreateUser(['view user email addresses'], 'mailUser@mailUser.com', FALSE, ['mail' => 'mailUser@mailUser.com']);
    $this->drupalLogin($mailUser);

    // See if the author is not obfuscated anymore:
    $this->drupalGet('/node/' . $node->id());
    $session->pageTextContains('Submitted by test@test.com on');
    $session->pageTextNotContains('Submitted by test on');

    // Logout and see what a user without the "View user email address" and
    // "View user information" can see:
    $this->drupalLogout();
    $this->drupalGet('/node/' . $node->id());
    $session->pageTextContains('Submitted by test on');
    $session->pageTextNotContains('Submitted by test@test.com on');
  }

  /**
   * Test the override on profile pages.
   */
  public function testDisplayOverrideOnProfilePage() {
    $session = $this->assertSession();
    // Create a test user with an already synced mail and username:
    $testUser = $this->drupalCreateUser([], 'test@test.com', FALSE, [
      'mail' => 'test@test.com',
    ]);

    // Login as a user without the 'view user email addresses' permission, but
    // with the 'access user profiles' permission:
    $accessUserRole = $this->createRole(['access user profiles']);
    $this->user->addRole($accessUserRole);
    $this->user->save();
    $this->drupalLogin($this->user);
    $this->config('email_registration_username.settings')->set('username_display_override_mode', 'email_registration')->save();

    // See if the author is obfuscated:
    $this->drupalGet('/user/' . $testUser->id());
    $session->elementTextEquals('css', 'h1', 'test');

    $this->drupalLogout();
    // Login as a user WITH the 'view user email addresses' and 'access user
    // profiles' permissions:
    $mailUser = $this->drupalCreateUser([
      'view user email addresses',
      'access user profiles',
    ], 'mailUser@mailUser.com', FALSE, ['mail' => 'mailUser@mailUser.com']);
    $this->drupalLogin($mailUser);

    // See if the author is not obfuscated anymore:
    $this->drupalGet('/user/' . $testUser->id());
    $session->elementTextEquals('css', 'h1', 'test@test.com');
  }

}
