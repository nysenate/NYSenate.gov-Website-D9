<?php
namespace Drupal\session_limit;

/**
 * Tests the multiple session test functionality.
 */
class SessionLimitSessionTestCase extends SessionLimitBaseTestCase {

  /**
   * getInfo() returns properties that are displayed in the test selection form.
   */
  public static function getInfo() {
    return array(
      'name' => 'Session Limit MutiSession Tests',
      'description' => 'Ensure the multi session tests for SimpleTest work as expected',
      'group' => 'Session Limit',
    );
  }

  /**
   * setUp() performs any pre-requisite tasks that need to happen.
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp('session_limit');
  }

  /**
   * Test session stash and restore.
   *
   * Drupal Simpletest has no native ability to test over multiple sessions.
   * The session_limit tests add this functionality. This first test checks
   * that multiple sessions are working in SimpleTest by logging in as two
   * different users simultaneously via two cUrl sessions.
   */
  public function testSessionStashAndRestore() {

    // Create and log in our privileged user.
    $user1 = $this->drupalCreateUser(array('access content'));
    $user2 = $this->drupalCreateUser(array('access content'));

    // Make sure that session_limit does not interfere with
    // this test of the tests.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_behaviour', 0)->save();
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 100)->save();

    // Login under session 1.
    $this->drupalLogin($user1);
    $this->drupalGet('user');
    $this->assertText(t('Log out'), t('User is logged in under session 1.'));
    $this->assertText($user1->name, t('User1 is logged in under session 1.'));

    // Backup session 1.
    $session_1 = $this->stashSession();

    // Check session 2 is logged out.
    $this->drupalGet('node');
    $this->assertNoText(t('Log out'), t('Session 1 is shelved.'));

    // Login under session 2.
    $this->drupalLogin($user2);
    $this->drupalGet('user');
    $this->assertText(t('Log out'), t('User is logged in under session 2.'));
    $this->assertText($user2->name, t('User2 is logged in under session 2.'));

    // Backup session 2.
    $session_2 = $this->stashSession();

    // Switch to session 1.
    $this->restoreSession($session_1);

    // Check still logged in as session 1.
    $this->drupalGet('user');
    $this->assertText(t('Log out'), t('User is logged in under session 1.'));
    $this->assertText($user1->name, t('User1 is logged in under session 1.'));

    // Switch to session 2.
    $this->restoreSession($session_2);

    // Check still logged in as session 2.
    $this->drupalGet('user');
    $this->assertText(t('Log out'), t('User is logged in under session 2.'));
    $this->assertText($user2->name, t('User2 is logged in under session 2.'));
  }
}
