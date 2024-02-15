<?php
namespace Drupal\session_limit;

/**
 * Session limit functionality when behaviour is to prevent login at limit.
 */
class SessionLimitPreventTestCase extends SessionLimitBaseTestCase {

  /**
   * getInfo() returns properties that are displayed in the test selection form.
   */
  public static function getInfo() {
    return array(
      'name' => 'Session Limit Prevent Tests',
      'description' => 'Ensure that the session limit module functions as expected when behaviour is set to prevent new sessions',
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
   * Test user can only have 1 session, prevent new sessions.
   */
  public function testSessionPreventOnMax1() {
    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 1)->save();

    $this->assertSessionPrevent(1);
    $this->closeAllSessions();
  }

  /**
   * Test user can only have 2 sessions, prevent new sessions.
   */
  public function testSessionPreventOnMax2() {
    // Set the default session limit.
    \Drupal::configFactory()->getEditable('session_limit.settings')->set('session_limit_max', 1)->save();

    $this->assertSessionPrevent(2);
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
    $this->assertSessionPrevent(4, $user);
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
    $this->assertSessionPrevent(5, $user);
  }

  /**
   * Checks that a user session limit is always used in preference to all others.
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

    // Check if the user has access to 1 session, the user specified max.
    $this->assertSessionPrevent(1, $user);
  }

}
