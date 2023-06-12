<?php

namespace Drupal\Tests\rh_user\Functional;

use Drupal\Core\Url;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;

/**
 * Test the functionality of the rabbit hole form additions to the user entity.
 *
 * @group rh_user
 */
class UserBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'user';

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'user';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_user', 'user'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $behaviorSettingsManager;

  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_ACTION = 'bundle_default';

  /**
   * Nothing to test here, user entity/bundle already exists.
   */
  public function testBundleCreation() {}

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    // There is nothing to create here. The user entity/bundle already exists.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'rh_action' => $action,
      'rh_override' => $override,
    ];
    $this->drupalGet('/admin/config/people/accounts');
    $this->assertRabbitHoleSettings();
    $this->submitForm($edit, 'Save configuration');
    return 'user';
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateUser([], $this->randomMachineName(), FALSE, $values)->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('user.admin_create');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.user.edit_form', ['user' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.user.admin_form');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return ['administer account settings', 'administer users'];
  }

}
