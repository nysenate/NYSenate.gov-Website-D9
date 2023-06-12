<?php

namespace Drupal\Tests\rh_group\Functional;

use Drupal\Core\Url;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;

/**
 * Test the functionality of the rabbit hole form additions to the Group.
 *
 * @requires module group
 * @group rh_group
 */
class GroupBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  /**
   * Test group type.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'group';

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'group_type';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_group', 'group'];

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    // TODO: Switch to trait when/if the patch is committed and released.
    // See: https://www.drupal.org/project/group/issues/3177542
    $storage = \Drupal::entityTypeManager()->getStorage('group_type');
    $group_type = $storage->create([
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_type);
    $this->bundle = $group_type;
    return $group_type->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/group/types/add');
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Save group type';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertRabbitHoleSettings();

    $edit = [
      'label' => $this->randomString(),
      'id' => mb_strtolower($this->randomMachineName()),
      'rh_action' => $action,
      'rh_override' => $override,
    ];
    $this->submitForm($edit, $submit_button);
    $this->bundle = $this->loadBundle($edit['id']);
    return $edit['id'];
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }

    // TODO: Switch to trait when/if the patch is committed and released.
    // See: https://www.drupal.org/project/group/issues/3177542
    $storage = \Drupal::entityTypeManager()->getStorage('group');
    $group = $storage->create($values + [
      'type' => $this->bundle->id(),
      'label' => $this->randomString(),
    ]);
    $group->enforceIsNew();
    $storage->save($group);
    return $group->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('entity.group.add_form', ['group_type' => $this->bundle->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.group.edit_form', ['group' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.group_type.edit_form', ['group_type' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return ['administer group', 'bypass group access', 'access group overview'];
  }

}
