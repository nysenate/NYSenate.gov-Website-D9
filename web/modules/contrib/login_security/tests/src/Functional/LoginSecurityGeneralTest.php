<?php

namespace Drupal\Tests\login_security\Functional;

/**
 * This class provides methods specifically for testing something.
 *
 * @group login_security
 */
class LoginSecurityGeneralTest extends LoginSecurityTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'test_page_test',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

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
    // Go to uninstallation page an uninstall login_security:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-login-security');
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
   * Test login message.
   */
  public function testLoginMessage() {
    $this->drupalLogout();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');

    $config->set('last_login_timestamp', 1)->save();
    $config->set('last_access_timestamp', 1)->save();

    $this->drupalLogin($this->user);
    // This is the very first login ever, so there should be no previous login
    // to show.
    $this->assertNoTextLastLoginMessage();

    $config->set('last_login_timestamp', 0)->save();
    $config->set('last_access_timestamp', 0)->save();

    $this->drupalLogin($this->user);
    $this->assertNoTextLastLoginMessage();
    $this->assertNoTextLastPageAccess();

    $config->set('last_login_timestamp', 1)->save();
    $this->drupalLogin($this->user);
    $this->assertTextLastLoginMessage();
    $this->assertNoTextLastPageAccess();

    $config->set('last_login_timestamp', 0)->save();
    $config->set('last_access_timestamp', 1)->save();
    $this->drupalLogin($this->user);
    $this->assertNoTextLastLoginMessage();
    $this->assertTextLastPageAccess();

    $config->set('last_login_timestamp', 1)->save();
    $this->drupalLogin($this->user);
    $this->assertTextLastLoginMessage();
    $this->assertTextLastPageAccess();
  }

  /**
   * Tests the disable login failure error message setting.
   *
   * Tests if disabling the login error messages, actually disables the
   * error messages.
   */
  public function testDisableLoginFailureErrorMessage() {
    $this->drupalLogout();
    $session = $this->assertSession();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Set the user login limit to 1:
    $config->set('user_wrong_count', 1)
      ->save();

    // Login with an invalid password:
    $this->invalidPwLogin($this->user);
    // An error should appear:
    $session->pageTextContains('Unrecognized username or password. Forgot your password?');

    // Login again:
    $this->invalidPwLogin($this->user);
    // The user block error message should appear:
    $session->pageTextContains('The username ' . $this->user->getAccountName() . ' has not been activated or is blocked');

    // Now reset the "login_security_track" table and disable error logging:
    _login_security_remove_all_events(\Drupal::time()->getRequestTime() + 3600);
    $config->set('disable_core_login_error', 1)->save();

    // Login with an invalid password:
    $this->invalidPwLogin($this->user);
    // An error should not appear:
    $session->pageTextNotContains('Unrecognized username or password. Forgot your password?');

    // Login again:
    $this->invalidPwLogin($this->user);
    // The user block error message should not appear either:
    $session->pageTextNotContains('The username ' . $this->user->getAccountName() . ' has not been activated or is blocked');
  }

}
