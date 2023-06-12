<?php

namespace Drupal\Tests\rh_taxonomy\Functional;

use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorInvocationTestBase;

/**
 * Test that rabbit hole behaviors are invoked correctly for user entities.
 *
 * @group rh_user
 */
class UserBehaviorInvocationTest extends RabbitHoleBehaviorInvocationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'user';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_user', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle($action = NULL) {
    // We can't create a new bundle, but we can save Rabbit Hole settings.
    if (isset($action)) {
      $this->behaviorSettingsManager->saveBehaviorSettings([
        'action' => $action,
        'allow_override' => TRUE,
      ], 'user', NULL);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateUser([], $this->randomMachineName(), FALSE, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewPermissions() {
    return ['access user profiles'];
  }

}
