<?php
namespace Drupal\session_limit;

/**
 * Session limit functions when expected behaviour is to logout oldest session.
 */
class SessionLimitLogoutTestCase extends SessionLimitBaseTestCase {

  /**
   * getInfo() returns properties that are displayed in the test selection form.
   */
  public static function getInfo() {
    return array(
      'name' => 'Session Limit Logout Tests',
      'description' => 'Ensure that the session limit module functions as expected when behaviour is set to logout oldest session',
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
   * Test user can only have 1 session, logout oldest.
   */
  public function testSessionLogoutOnMax1() {
    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 1)->save();

    $this->assertSessionLogout(1);
    $this->closeAllSessions();
  }

  /**
   * Test user can only have 2 sessions, logout oldest.
   */
  public function testSessionLogoutOnMax2() {
    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 2)->save();

    $this->assertSessionLogout(2);
    $this->closeAllSessions();
  }

  /**
   * Checks that the session limit is returned correctly by a role.
   */
  public function testSessionLimitRoles() {
    // Create a test user.
    $user = $this->drupalCreateUser(array('access content'));
    $roles = $this->sessionLimitMakeRoles($user);

    // Set the session limits for the roles.
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[0]->rid, 2);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[1]->rid, 4);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[2]->rid, 3);


    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 1)->save();

    // Check if the user has access to 4 sessions.
    $this->assertSessionLogout(4, $user);
  }

  /**
   * Checks that the session limit is returned correctly by a user override.
   */
  public function testSessionLimitUser() {
    // Create a test user.
    $user = $this->drupalCreateUser();
    $roles = $this->sessionLimitMakeRoles($user);

    // Add a personal session limit.
    // @FIXME
// user_save() is now a method of the user entity.
// user_save($user, array('data' => array('session_limit' => 5)));


    // Set the session limits for the roles.
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[0]->rid, 2);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[1]->rid, 3);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[2]->rid, 4);


    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 1)->save();

    // Check if the user has access to 5 sessions.
    $this->assertSessionLogout(5, $user);
  }

  /**
   * Check that user override takes precedence over default and role regardless of max.
   */
  public function testSessionLimitUserMaxPrecedence() {
    // Create a test user.
    $user = $this->drupalCreateUser();
    $roles = $this->sessionLimitMakeRoles($user);

    // Add a personal session limit.
    // @FIXME
// user_save() is now a method of the user entity.
// user_save($user, array('data' => array('session_limit' => 1)));


    // Set the session limits for the roles.
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[0]->rid, 3);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[1]->rid, 4);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set('session_limit_rid_' . $roles[2]->rid, 5);


    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 2)->save();

    // Check if the user has access to only 1 session.
    $this->assertSessionLogout(1, $user);
  }
}
