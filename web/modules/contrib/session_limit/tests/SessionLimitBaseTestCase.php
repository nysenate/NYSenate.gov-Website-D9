<?php
namespace Drupal\session_limit;

/**
 * Base test for session limits.
 *
 * This contains a collection of helper functions and session_limit
 * assertions.
 */
class SessionLimitBaseTestCase extends DrupalWebTestCase {

  /**
   * A store references to different sessions.
   */
  protected $curlHandles = array();
  protected $loggedInUsers = array();

  /**
   * Session limit helper function to create 3 roles.
   *
   * @param stdClass $user
   *   (optional) If provided the user will be added to
   *   the three roles.
   *
   * @return array
   *   An array of the three roles.
   */
  public function sessionLimitMakeRoles($user = NULL) {
    // Create roles.
    $roles = array();

    $roles[] = (object) array('name' => 'role1');
    $roles[] = (object) array('name' => 'role2');
    $roles[] = (object) array('name' => 'role3');
    user_role_save($roles[0]);
    user_role_save($roles[1]);
    user_role_save($roles[2]);

    if (!empty($user)) {
      $user->roles[$roles[0]->rid] = $roles[0]->name;
      $user->roles[$roles[1]->rid] = $roles[1]->name;
      $user->roles[$roles[2]->rid] = $roles[2]->name;
      $user->save();
    }

    return $roles;
  }

  /**
   * Test that an individual user can have up to a specifed number of sessions.
   *
   * Once the maximum is reached, the oldest session is logged out.
   *
   * @param int $session_limit
   *   The max number of sessions the specified user should be able to access.
   * @param stdClass $user
   *   (optional) The user to test this with. Leave blank to create a user.
   */
  public function assertSessionLogout($session_limit, stdClass $user = NULL) {
    // Set the session limit behaviour to log out of old sessions.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_behaviour', 1)->save();
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_logged_out_message', 'Reached limit @number.')->save();

    // Create the user to test with.
    $user = empty($user) ? $this->drupalCreateUser(array('access content')) : $user;

    $sessions = array();

    for ($session_number = 1; $session_number <= $session_limit; $session_number++) {

      // Log user into each session.
      $this->drupalLogin($user);
      $this->drupalGet('user');
      $this->assertText(t('Log out'), t('User is logged in under session @no.', array('@no' => $session_number)));
      $this->assertText($user->name, t('User is logged in under session @no.', array('@no' => $session_number)));

      // Backup session.
      $sessions[$session_number] = $this->stashSession();

      // Wait briefly to prevent race conditions.
      sleep(1);
    }

    // Check all allowed sessions are currently accessible.
    foreach ($sessions as $session_number => $session_id) {
      $this->restoreSession($session_id);
      $this->drupalGet('user');
      $this->assertText(t('Log out'), t('User is logged in under session @no.', array('@no' => $session_number)));
      $this->assertText($user->name, t('User is logged in under session @no.', array('@no' => $session_number)));
    }

    // Create a further session.
    $extra_session_number = $session_limit + 1;
    $this->stashSession();
    $this->drupalLogin($user);
    $this->drupalGet('user');
    $this->assertText(t('Log out'), t('User is logged in under session @no.', array('@no' => $extra_session_number)));
    $this->assertText($user->name, t('User is logged in under session @no.', array('@no' => $extra_session_number)));

    // Check user 1 is no longer logged in on session 1.
    $sessions[$extra_session_number] = $this->stashSession();
    $this->restoreSession($sessions[1]);
    $this->drupalGet('node');
    $this->assertNoText(t('Log out'), t('User 1 is not logged in under session 1.'));

    $this->assertText(t('Reached limit @number.', array('@number' => $session_limit)), t('User was shown session limit message.'));

    // Check user 1 is logged in on all other sessions.
    foreach ($sessions as $session_number => $session_id) {
      if ($session_number == 1) {
        // We know they have been logged out of session 1.
        continue;
      }

      $this->restoreSession($session_id);
      $this->drupalGet('user');
      $this->assertText(t('Log out'), t('User is logged in under session @no.', array('@no' => $session_number)));
      $this->assertText($user->name, t('User is logged in under session @no.', array('@no' => $session_number)));
    }
  }

  /**
   * Test that new sessions cannot be created past a maximum.
   *
   * This tests the session_limit 'prevent new sessions' behaviour once
   * the maximum is reached.
   *
   * @param int $session_limit
   *   The maximum number of allowed sessions.
   */
  public function assertSessionPrevent($session_limit) {
    // Set the session limit behaviour to prevent new sessions.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_behaviour', 2)->save();

    // Set the default session limit to 1.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', $session_limit)->save();

    // Create the user to test with.
    $user = $this->drupalCreateUser(array('access content'));

    $sessions = array();

    for ($session_number = 1; $session_number <= $session_limit; $session_number++) {

      // Log user into each session.
      $this->drupalLogin($user);
      $this->drupalGet('user');
      $this->assertText(t('Log out'), t('User is logged in under session @no.', array('@no' => $session_number)));
      $this->assertText($user->name, t('User is logged in under session @no.', array('@no' => $session_number)));

      // Backup session.
      $sessions[$session_number] = $this->stashSession();
    }

    // Check all allowed sessions are currently accessible.
    foreach ($sessions as $session_number => $session_id) {
      $this->restoreSession($session_id);
      $this->drupalGet('user');
      $this->assertText(t('Log out'), t('User is logged in under session @no.', array('@no' => $session_number)));
      $this->assertText($user->name, t('User is logged in under session @no.', array('@no' => $session_number)));
    }

    // Try to login on a further session.
    $this->stashSession();
    $this->drupalLoginExpectFail($user);
    $this->assertText(t('The maximum number of simultaneous sessions (@session_limit) for your account has been reached. You did not log off from a previous session or someone else is logged on to your account. This may indicate that your account has been compromised or that account sharing is limited on this site. Please contact the site administrator if you suspect your account has been compromised.', array('@session_limit' => $session_limit)), t('User sees the session limit login prevention message.'));

    // Switch back to session 1 and logout.
    $extra_session_number = $session_limit + 1;
    $sessions[$extra_session_number] = $this->stashSession();
    $this->restoreSession($sessions[1]);
    $this->drupalLogout($user);
    $this->drupalGet('node');
    $this->assertNoText(t('Log out'), t('User has logged out of session 1.'));

    // Switch back to extra session and check they can now login.
    $this->restoreSession($sessions[$extra_session_number]);
    $this->drupalLogin($user);
    $this->drupalGet('node');
    $this->assertText(t('Log out'), t('User has logged into the extra session now they have logged out of session 1.'));
  }

  /**
   * Initialise a new unique session.
   *
   * @return string
   *   Unique identifier for the session just stored.
   *   It is the cookiefile name.
   */
  public function stashSession() {
    if (empty($this->cookieFile)) {
      // No session to stash.
      return;
    }

    // The session_id is the current cookieFile.
    $session_id = $this->cookieFile;

    $this->curlHandles[$session_id] = $this->curlHandle;
    $this->loggedInUsers[$session_id] = $this->loggedInUser;

    // Reset Curl.
    unset($this->curlHandle);
    $this->loggedInUser = FALSE;

    // Set a new unique cookie filename.
    do {
      $this->cookieFile = $this->public_files_directory . '/' . $this->randomName() . '.jar';
    }
    while (isset($this->curlHandles[$this->cookieFile]));

    return $session_id;
  }

  /**
   * Restore a previously stashed session.
   *
   * @param string $session_id
   *   The session to restore as returned by stashSession();
   *   This is also the path to the cookie file.
   *
   * @return string
   *   The old session id that was replaced.
   */
  public function restoreSession($session_id) {
    $old_session_id = NULL;

    if (isset($this->curlHandle)) {
      $old_session_id = $this->stashSession();
    }

    // Restore the specified session.
    $this->curlHandle = $this->curlHandles[$session_id];
    $this->cookieFile = $session_id;
    $this->loggedInUser = $this->loggedInUsers[$session_id];

    return $old_session_id;
  }

  /**
   * Close all stashed sessions and the current session.
   */
  public function closeAllSessions() {
    foreach ($this->curlHandles as $cookie_file => $curl_handle) {
      if (isset($curl_handle)) {
        curl_close($curl_handle);
      }
    }

    // Make the server forget all sessions.
    db_truncate('sessions')->execute();

    $this->curlHandles = array();
    $this->loggedInUsers = array();
    $this->loggedInUser = FALSE;
    $this->cookieFile = $this->public_files_directory . '/' . $this->randomName() . '.jar';
    unset($this->curlHandle);
  }

  /**
   * Log in a user with the internal browser but expect this to fail.
   *
   * This works as drupalLogin but instead of checking if the login succeeded,
   * it instead checks for not being logged in and fails if it has managed
   * to login successfully.
   *
   * @param $account
   *   User object representing the user to log in.
   *
   * @see drupalCreateUser()
   */
  protected function drupalLoginExpectFail(stdClass $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $edit = array(
      'name' => $account->name,
      'pass' => $account->pass_raw
    );
    $this->drupalPost('user', $edit, t('Log in'));

    // Check that login was unsuccessful by ensuring there is no log out link.
    $pass = $this->assertNoLink(t('Log out'), 0, t('User %name did not log in.', array('%name' => $account->name)), t('User login'));

    if (!$pass) {
      $this->loggedInUser = $account;
    }
  }
}
