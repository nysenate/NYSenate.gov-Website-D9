<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test Login Security's soft blocking restrictions.
 *
 * @group login_security
 */
class LoginSecurityHostBlockingTest extends LoginSecurityTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  protected static $modules = ['node'];

  /**
   * A normal user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $normalUser;

  /**
   * Another normal user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $secondUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalUser = $this->drupalCreateUser([]);
    $this->secondUser = $this->drupalCreateUser([]);

    $this->createContentType(['type' => 'article']);
    $this->createNode([
      'type' => 'article',
      'id' => 1,
      'title' => 'test123',
    ]);

  }

  /**
   * Test normal login with the host_wrong_count set.
   */
  public function testNormalLoginSoft() {
    // Set invalid login count to an abritary number (5 for example), just to
    // generally activate this option:
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('host_wrong_count', 5)
      ->set('notice_attempts_available', 1)
      ->save();

    $this->validLogin($this->normalUser);

    $warning_message = 'You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.';
    $this->assertSession()->pageTextNotContains($warning_message);
  }

  /**
   * Test normal login with the host_wrong_count_hard set.
   */
  public function testNormalLoginHard() {
    // Set invalid login count to an abritary number (5 for example), just to
    // generally activate this option:
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('host_wrong_count_hard', 5)
      ->set('notice_attempts_available', 1)
      ->save();

    $this->validLogin($this->normalUser);

    $warning_message = 'You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.';
    $this->assertSession()->pageTextNotContains($warning_message);
  }

  // @codingStandardsIgnoreStart
  // @todo Login notices currently do not work, see
  // https://www.drupal.org/project/login_security/issues/2936647
  // /**
  //  * Tests the remaining login attempt notice for the host soft lock option.
  //  */
  // public function testUserNoticeLoginSoft() {
  //   $config = \Drupal::configFactory()->getEditable('login_security.settings');
  //   $config->set('host_wrong_count', 5)
  //     ->set('notice_attempts_available', 0)
  //     ->save();

  //   // Login with notices disabled:
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->pageTextNotContains('You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.');
  //   // Login with notices enabled:
  //   $config->set('notice_attempts_available', 1)->save();
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->pageTextContains('You have used 2 out of 5 login attempts. After all 5 have been used, you will be unable to login.');
  // }

  // /**
  //  * Tests the remaining login attempt notice for the soft lock option.
  //  */
  // public function testUserNoticeLoginHard() {
  //   $config = \Drupal::configFactory()->getEditable('login_security.settings');
  //   $config->set('host_wrong_count_hard', 5)
  //     ->set('notice_attempts_available', 0)
  //     ->save();

  //   // Login with notices disabled:
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->pageTextNotContains('You have used 1 out of 5 login attempts. After all 5 have been used, you will be unable to login.');
  //   // Login with notices enabled:
  //   $config->set('notice_attempts_available', 1)->save();
  //   $this->invalidLogin($this->normalUser);
  //   $this->assertSession()->pageTextContains('You have used 2 out of 5 login attempts. After all 5 have been used, you will be unable to login.');
  // }


  /**
   * Test host soft blocking with invalid password login.
   */
  public function testPwSoftBlocking() {
    $session = $this->assertSession();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Allow 2 attempts to login before being soft-blocking is enforced:
    $config->set('host_wrong_count', 2)
      // Enable user noticing for the remaining login attempts:
      // @todo This doesn't currently work, see
      // https://www.drupal.org/project/login_security/issues/2936647
      ->set('notice_attempts_available', 1)
      ->save();

    // First try:
    $this->invalidPwLogin($this->normalUser);
    // $session->pageTextContains('You have used 1 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Second try:
    $this->invalidPwLogin($this->normalUser);
    // $session->pageTextContains('You have used 2 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Third try:
    $this->invalidPwLogin($this->normalUser);
    $session->pageTextContains('This host is not allowed to log in');
    // $session->pageTextContains('The user ' . $this->normalUser->getAccountName() . ' has been blocked due to failed login attempts.');
    $this->onLoginScreen();

    // Try a normal login with the same account, as this shouldn't be possible
    // anymore:
    $this->validLogin($this->normalUser);
    $session->pageTextContains('This host is not allowed to log in');
    $this->onLoginScreen();

    // Try a login with a different account, as this should also not be
    // possible:
    $this->validLogin($this->secondUser);
    $session->pageTextContains('This host is not allowed to log in');
    $this->onLoginScreen();

    // Try browsing site contents as an anonymous user, this should still be
    // possible, as we are only soft locked:
    $this->drupalGet('node/1');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123');
  }

  /**
   * Test host hard blocking with invalid password login.
   */
  public function testPwHardBlocking() {
    $session = $this->assertSession();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Allow 2 attempts to login before being soft-blocking is enforced:
    $config->set('host_wrong_count_hard', 2)
      // Enable user noticing for the remaining login attempts:
      // @todo This doesn't currently work, see
      // https://www.drupal.org/project/login_security/issues/2936647
      ->set('notice_attempts_available', 1)
      ->save();

    // First try:
    $this->invalidPwLogin($this->normalUser);
    // $session->pageTextContains('You have used 1 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Second try:
    $this->invalidPwLogin($this->normalUser);
    // $session->pageTextContains('You have used 2 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Third try, now any page on the site should be blocked:
    $this->drupalGet('user');
    $session->pageTextContains('has been banned');
    $session->statusCodeEquals(403);

    // Go to a node:
    $this->drupalGet('node/1');
    $session->pageTextContains('has been banned');
    $session->statusCodeEquals(403);

    // Go to an admin page
    $this->drupalGet('admin');
    $session->pageTextContains('has been banned');
    $session->statusCodeEquals(403);
  }

  /**
   * Test host soft blocking with invalid user login.
   */
  public function testUsernameSoftBlocking() {
    $session = $this->assertSession();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Allow 2 attempts to login before being soft-blocking is enforced:
    $config->set('host_wrong_count', 2)
      // Enable user noticing for the remaining login attempts:
      // @todo This doesn't currently work, see
      // https://www.drupal.org/project/login_security/issues/2936647
      ->set('notice_attempts_available', 1)
      ->save();

    // First try:
    $this->invalidUsernameLogin();
    // $session->pageTextContains('You have used 1 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Second try:
    $this->invalidUsernameLogin();
    // $session->pageTextContains('You have used 2 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Third try:
    $this->invalidUsernameLogin();
    $session->pageTextContains('This host is not allowed to log in');
    // $session->pageTextContains('The user ' . $this->normalUser->getAccountName() . ' has been blocked due to failed login attempts.');
    $this->onLoginScreen();

    // Try a normal login with the same account, as this shouldn't be possible
    // anymore:
    $this->validLogin($this->normalUser);
    $session->pageTextContains('This host is not allowed to log in');
    $this->onLoginScreen();

    // Try a login with a different account, as this should also not be
    // possible:
    $this->validLogin($this->secondUser);
    $session->pageTextContains('This host is not allowed to log in');
    $this->onLoginScreen();

    // Try browsing site contents as an anonymous user, this should still be
    // possible, as we are only soft locked:
    $this->drupalGet('node/1');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123');
  }

  /**
   * Test host hard blocking with invalid user login.
   */
  public function testUsernameHardBlocking() {
    $session = $this->assertSession();
    $config = \Drupal::configFactory()->getEditable('login_security.settings');
    // Allow 2 attempts to login before being soft-blocking is enforced:
    $config->set('host_wrong_count_hard', 2)
      // Enable user noticing for the remaining login attempts:
      // @todo This doesn't currently work, see
      // https://www.drupal.org/project/login_security/issues/2936647
      ->set('notice_attempts_available', 1)
      ->save();

    // First try:
    $this->invalidUsernameLogin();
    // $session->pageTextContains('You have used 1 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Second try:
    $this->invalidUsernameLogin();
    // $session->pageTextContains('You have used 2 out of 2 login attempts. After all 2 have been used, you will be unable to login.');
    $this->onLoginScreen();

    // Third try, now any page on the site should be blocked:
    $this->drupalGet('user');
    $session->pageTextContains('has been banned');
    $session->statusCodeEquals(403);

    // Go to a node:
    $this->drupalGet('node/1');
    $session->pageTextContains('has been banned');
    $session->statusCodeEquals(403);

    // Go to an admin page
    $this->drupalGet('admin');
    $session->pageTextContains('has been banned');
    $session->statusCodeEquals(403);
  }
  // @codingStandardsIgnoreEnd

}
