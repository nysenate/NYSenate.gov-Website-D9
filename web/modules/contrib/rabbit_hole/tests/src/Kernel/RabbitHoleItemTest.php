<?php

namespace Drupal\Tests\rabbit_hole\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the entity API for the Rabbit Hole field type.
 *
 * @group rabbit_hole
 */
class RabbitHoleItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rabbit_hole'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('entity_test', 'entity_test');
  }

  /**
   * Tests using entity fields of the Rabbit Hole field type.
   */
  public function testRabbitHoleField() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $entity->rabbit_hole__settings = 'page_not_found';
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->rabbit_hole__settings);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->rabbit_hole__settings[0]);
    $this->assertEquals('page_not_found', $entity->rabbit_hole__settings->action);
    $this->assertNull($entity->rabbit_hole__settings->settings);

    // Verify changing the field value and adding settings.
    $new_action = 'page_redirect';
    $new_settings = [
      'redirect' => '/hello',
      'redirect_code' => 301,
      'redirect_fallback_action' => 'page_not_found',
    ];
    $entity->rabbit_hole__settings->action = $new_action;
    $entity->rabbit_hole__settings->settings = $new_settings;
    $entity->save();

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($new_action, $entity->rabbit_hole__settings->action);
    $this->assertEquals($new_settings, $entity->rabbit_hole__settings->settings);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->rabbit_hole__settings->generateSampleItems();
    $actions = \Drupal::service('plugin.manager.rabbit_hole_behavior_plugin')->getBehaviors();
    $this->assertArrayHasKey($entity->rabbit_hole__settings->action, $actions);
    $this->entityValidateAndSave($entity);
  }

}
