<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the RabbitHoleBehaviorSettings configuration entity functionality.
 *
 * @group rabbit_hole
 */
class RabbitHoleBehaviorSettingsTest extends BrowserTestBase {
  const DEFAULT_TEST_ENTITY = 'node';
  const DEFAULT_TEST_ENTITY_TYPE = 'node_type';
  const DEFAULT_ACTION = 'bundle_default';
  const DEFAULT_OVERRIDE = BehaviorSettings::OVERRIDE_ALLOW;
  const DEFAULT_REDIRECT_CODE = BehaviorSettings::REDIRECT_NOT_APPLICABLE;
  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_BUNDLE_OVERRIDE = BehaviorSettings::OVERRIDE_ALLOW;
  const DEFAULT_BUNDLE_REDIRECT_CODE = BehaviorSettings::REDIRECT_NOT_APPLICABLE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rabbit_hole', self::DEFAULT_TEST_ENTITY];

  /**
   * Behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManager
   */
  private $behaviorSettingsManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Test content type.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $testNodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->behaviorSettingsManager = $this->container
      ->get('rabbit_hole.behavior_settings_manager');
    $this->testNodeType = $this->generateTestNodeType();
  }

  /**
   * Test that a BehaviorSettings can be found and contains correct values.
   *
   * Test that a saved BehaviorSettings entity can be found by the config system
   * and contains the correct values.
   */
  public function testSettings() {
    $this->saveAndTestExpectedValues(self::DEFAULT_ACTION,
      __METHOD__, self::DEFAULT_TEST_ENTITY_TYPE, $this->testNodeType->id());
  }

  /**
   * Test that the default bundle settings exist and have the expected values.
   */
  public function testBundleSettingsDefault() {
    $settings = \Drupal::config('rabbit_hole.behavior_settings.default');
    $this->assertEquals(self::DEFAULT_BUNDLE_ACTION,
        $settings->get('action'),
      'Unexpected default action');
    $this->assertEquals(self::DEFAULT_BUNDLE_OVERRIDE,
        $settings->get('allow_override'),
       'Unexpected default override');
    $this->assertEquals(self::DEFAULT_BUNDLE_REDIRECT_CODE,
        $settings->get('redirect_code'),
        'Unexpected default redirect');
  }

  /**
   * Test that a BehaviourSettings can be given an ID and found later.
   *
   * Test that a saved BehaviourSettings entity can be given an ID based on
   * a generated bundle (a NodeType in this case) and be found based on that ID.
   */
  public function testBundleSettings() {
    $this->saveAndTestExpectedValues('page_not_found', __METHOD__,
      self::DEFAULT_TEST_ENTITY_TYPE, $this->testNodeType->id());
    $this->deleteTestNodeType();
  }

  /**
   * Test loading behavior settings for a nonexistent bundle returns defaults.
   */
  public function testLoadBundleSettingsWithDefault() {
    // We search for a bundle that doesn't exist (named from a UUID) expecting
    // to receive the default value.
    $action = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig(
      self::DEFAULT_TEST_ENTITY,
      'f4515736-cfa0-4e38-b3ed-1306f56bd2a1')->get('action');
    $this->assertEquals($action, self::DEFAULT_BUNDLE_ACTION,
      'Unexpected default action');
  }

  /**
   * Test loading editable for nonexistent behavior settings returns NULL.
   */
  public function testLoadNullEditable() {
    $editable = $this->behaviorSettingsManager
      ->loadBehaviorSettingsAsEditableConfig(self::DEFAULT_TEST_ENTITY,
        '6b92ed36-f17f-4799-97d0-ae1801ed37ff');
    $this->assertNull($editable);
  }

  /**
   * Helper function to test saving and confirming config.
   */
  private function saveAndTestExpectedValues($expected_action, $calling_method, $entity_type_label = '', $entity_id = NULL) {

    // Delete key if it already exists.
    $editable = $this->behaviorSettingsManager->loadBehaviorSettingsAsEditableConfig(
      $entity_type_label, $entity_id);
    if (isset($editable)) {
      $editable->delete();
    }

    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => $expected_action,
      'allow_override' => 0,
      'redirect_code' => 0,
      'redirect' => '',
    ], $entity_type_label, $entity_id);
    $action = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig(
      $entity_type_label, $entity_id)->get('action');
    $this->assertEquals($expected_action, $action, 'Unexpected action '
      . ' (called from ' . $calling_method . ')');

    // Clean up the entity afterwards.
    $this->behaviorSettingsManager->loadBehaviorSettingsAsEditableConfig(
      $entity_type_label, $entity_id)->delete();
  }

  /**
   * Helper function to generate the test node type.
   */
  private function generateTestNodeType() {
    $type = NodeType::create([
      'type' => 'test_behavior_settings_node_type',
      'name' => 'Test Behavior Settings Node Type',
    ]
    );

    $type->save();

    return $type;
  }

  /**
   * Helper function to delete the test node type from the database.
   */
  private function deleteTestNodeType() {
    $this->testNodeType->delete();
  }

}
