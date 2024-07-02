<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test Rabbit Hole permissions.
 *
 * @group rabbit_hole
 */
class RabbitHolePermissionsTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rabbit_hole'];

  /**
   * Tests dynamic Rabbit Hole permissions.
   */
  public function testRabbitHoleDynamicPermissions() {
    \Drupal::service('module_installer')->install(['taxonomy']);

    // Verify that Rabbit Hole permissions are not available yet.
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertFalse(isset($permissions['rabbit hole administer taxonomy_term']));
    $this->assertFalse(isset($permissions['rabbit hole bypass taxonomy_term']));

    // Enable taxonomy entity type in Rabbit Hole settings and verify that
    // permissions exist.
    \Drupal::service('rabbit_hole.behavior_settings_manager')->enableEntityType('taxonomy_term');
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertTrue(isset($permissions['rabbit hole administer taxonomy_term']));
    $this->assertTrue(isset($permissions['rabbit hole bypass taxonomy_term']));

    $this->assertEquals(['module' => ['taxonomy']], $permissions['rabbit hole administer taxonomy_term']['dependencies']);

    // Now disable and verify again.
    \Drupal::service('rabbit_hole.behavior_settings_manager')->disableEntityType('taxonomy_term');
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertFalse(isset($permissions['rabbit hole administer taxonomy_term']));
    $this->assertFalse(isset($permissions['rabbit hole bypass taxonomy_term']));
  }

}
