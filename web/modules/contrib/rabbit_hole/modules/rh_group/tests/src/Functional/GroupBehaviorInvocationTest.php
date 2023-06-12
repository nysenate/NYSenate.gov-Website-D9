<?php

namespace Drupal\Tests\rh_group\Functional;

use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorInvocationTestBase;

/**
 * Test that rabbit hole behaviors are invoked correctly for groups.
 *
 * @requires module group
 * @group rh_group
 */
class GroupBehaviorInvocationTest extends RabbitHoleBehaviorInvocationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'group';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_group', 'group'];

  /**
   * Group type.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle($action = NULL) {
    // TODO: Switch to trait when/if the patch is committed and released.
    // See: https://www.drupal.org/project/group/issues/3177542
    $storage = \Drupal::entityTypeManager()->getStorage('group_type');
    $group_type = $storage->create([
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_type);
    $this->groupType = $group_type;

    if (isset($action)) {
      $this->behaviorSettingsManager->saveBehaviorSettings([
        'action' => $action,
        'allow_override' => TRUE,
      ], 'group_type', $this->groupType->id());
    }
    return $this->groupType->id();
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
      'type' => $this->groupType->id(),
      'label' => $this->randomString(),
    ]);
    $group->enforceIsNew();
    $storage->save($group);

    return $group;
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewPermissions() {
    return ['bypass group access'];
  }

}
