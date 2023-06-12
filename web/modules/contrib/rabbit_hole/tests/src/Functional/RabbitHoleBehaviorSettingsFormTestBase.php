<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the Rabbit Hole form additions tests.
 */
abstract class RabbitHoleBehaviorSettingsFormTestBase extends BrowserTestBase {

  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_ACTION = 'bundle_default';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The name of bundle entity type.
   *
   * @var string
   */
  protected $bundleEntityTypeName;

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $behaviorSettingsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->behaviorSettingsManager = $this->container->get('rabbit_hole.behavior_settings_manager');
    $admin_permissions = array_merge($this->getAdminPermissions(), ['rabbit hole administer ' . $this->entityType]);
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
  }

  /**
   * Test that bundle form contains Rabbit Hole settings and required fields.
   */
  public function testDefaultBundleForm() {
    $bundle_id = $this->createEntityBundle();
    $this->loadEntityBundleForm($bundle_id);

    $this->assertRabbitHoleSettings();
    $this->assertSession()->fieldValueEquals('rh_override', BehaviorSettings::OVERRIDE_ALLOW);
    $this->assertSession()->checkboxChecked($this->getOptionId(static::DEFAULT_BUNDLE_ACTION));
  }

  /**
   * Test the "rabbit hole administer *" permission.
   *
   * User without "rabbit hole administer *" permission should not be able to
   * see and administer Rabbit Hole settings.
   */
  public function testAdministerPermission() {
    $this->drupalLogin($this->drupalCreateUser($this->getAdminPermissions()));

    $this->createEntityBundle();
    $this->drupalGet($this->getCreateEntityUrl());
    $this->assertNoRabbitHoleSettings();
  }

  /**
   * Test that Rabbit Hole settings are created together with entity bundle.
   */
  public function testBundleCreation() {
    $override = BehaviorSettings::OVERRIDE_DISALLOW;
    $action = 'access_denied';
    $bundle_id = $this->createEntityBundleFormSubmit($action, $override);

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig($this->bundleEntityTypeName, $bundle_id);
    $this->assertEquals($action, $saved_config->get('action'));
    $this->assertEquals($override, $saved_config->get('allow_override'));

    $this->loadEntityBundleForm($bundle_id);
    $this->assertSession()->fieldValueEquals('rh_override', $override);
    $this->assertSession()->checkboxChecked($this->getOptionId($action));
  }

  /**
   * Test the first bundle form save with Rabbit Hole configuration.
   */
  public function testBundleFormFirstSave() {
    $test_bundle_id = $this->createEntityBundle();
    $this->loadEntityBundleForm($test_bundle_id);

    $override = BehaviorSettings::OVERRIDE_DISALLOW;
    $action = 'access_denied';

    $this->submitForm([
      'rh_override' => $override,
      'rh_action' => $action,
    ], 'edit-submit');

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig($this->bundleEntityTypeName, $test_bundle_id);
    $this->assertEquals($action, $saved_config->get('action'));
    $this->assertEquals($override, $saved_config->get('allow_override'));
  }

  /**
   * Test Rabbit Hole settings with allowed/disallowed overrides.
   */
  public function testAllowOverrideValue() {
    $bundle_allow = $this->createEntityBundle();
    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => 'access_denied',
      'allow_override' => BehaviorSettings::OVERRIDE_ALLOW,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
      'redirect_fallback_action' => 'access_denied',
    ], $this->bundleEntityTypeName, $bundle_allow);
    $this->loadCreateEntityForm();
    $this->assertRabbitHoleSettings();

    $bundle_disallow = $this->createEntityBundle();
    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => 'access_denied',
      'allow_override' => BehaviorSettings::OVERRIDE_DISALLOW,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
      'redirect_fallback_action' => 'access_denied',
    ], $this->bundleEntityTypeName, $bundle_disallow);
    $this->loadCreateEntityForm();
    $this->assertNoRabbitHoleSettings();
  }

  /**
   * Test that bundle form with a configured bundle behaviour loads config.
   */
  public function testBundleFormExistingBehavior() {
    $action = 'page_not_found';
    $override = BehaviorSettings::OVERRIDE_DISALLOW;

    $test_bundle_id = $this->createEntityBundle();
    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => $action,
      'allow_override' => $override,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
    ], $this->bundleEntityTypeName, $test_bundle_id);

    $this->loadEntityBundleForm($test_bundle_id);

    $this->assertSession()->fieldValueEquals('rh_override', $override);
    $this->assertSession()->checkboxChecked($this->getOptionId($action));
  }

  /**
   * Test new changes to bundle with existing rabbit hole settings changes key.
   *
   * Test that saving changes to a bundle form which already has
   * configured rabbit hole behavior settings changes the existing key.
   */
  public function testBundleFormSave() {
    $test_bundle_id = $this->createEntityBundle();

    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => 'access_denied',
      'allow_override' => BehaviorSettings::OVERRIDE_DISALLOW,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
    ], $this->bundleEntityTypeName, $test_bundle_id);

    $this->loadEntityBundleForm($test_bundle_id);

    $action = 'page_not_found';
    $override = BehaviorSettings::OVERRIDE_ALLOW;

    $this->submitForm([
      'rh_override' => $override,
      'rh_action' => $action,
    ], 'edit-submit');

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig($this->bundleEntityTypeName, $test_bundle_id);

    $this->assertEquals($action, $saved_config->get('action'));
    $this->assertEquals($override, $saved_config->get('allow_override'));
  }

  /**
   * Test saving settings for entity that did not previously have them.
   *
   * Test that an existing entity that previously didn't have settings will have
   * settings saved when the entity form is saved.
   */
  public function testExistingEntityNoConfigSave() {
    $this->createEntityBundle();
    $entity_id = $this->createEntity();
    $this->loadEditEntityForm($entity_id);
    $action = 'access_denied';

    $this->submitForm([
      'rh_action' => $action,
    ], 'Save');

    $entity = $this->loadEntity($entity_id);
    $this->assertEquals($action, $entity->get('rh_action')->value);
  }

  /**
   * Test that existing entity is edited on saving the entity form.
   */
  public function testExistingEntitySave() {
    $this->createEntityBundle();
    $entity_id = $this->createEntity('display_page');
    $this->loadEditEntityForm($entity_id);
    $action = 'access_denied';

    $this->submitForm([
      'rh_action' => $action,
    ], 'Save');

    // Make sure the editor didn't hit error page after the form save.
    $this->assertSession()->statusCodeEquals(200);

    $entity = $this->loadEntity($entity_id);
    $this->assertEquals($action, $entity->get('rh_action')->value);
  }

  /**
   * Test that when entity form is loaded it defaults the bundle configuration.
   */
  public function testDefaultEntitySettingsLoad() {
    $this->createEntityBundle();
    $this->loadCreateEntityForm();

    $this->assertRabbitHoleSettings();
    $this->assertSession()->checkboxChecked($this->getOptionId(static::DEFAULT_ACTION));
  }

  /**
   * Test that entity form correctly loads previously saved behavior settings.
   */
  public function testExistingEntitySettingsLoad() {
    $this->createEntityBundle();

    $action = 'access_denied';
    $entity_id = $this->createEntity($action);
    $this->loadEditEntityForm($entity_id);

    $this->assertSession()->checkboxChecked($this->getOptionId($action));
  }

  /**
   * Test redirect after entity form save.
   */
  public function testEntityFormSaveRedirect() {
    $override = BehaviorSettings::OVERRIDE_DISALLOW;
    $action = 'access_denied';
    $this->createEntityBundleFormSubmit($action, $override);
    $this->loadCreateEntityForm();
    $this->assertNoRabbitHoleSettings();
    $this->submitForm([], $this->getEntityFormSubmit());

    // Make sure the editor didn't hit error page after the form save in case
    // there is no Rabbit Hole actions available.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Combines multiple asserts to check the "Rabbit Hole" settings fieldset.
   */
  protected function assertRabbitHoleSettings() {
    $this->assertSession()->fieldExists('rh_action');
    $this->assertSession()->fieldExists('edit-rh-action-access-denied');
    $this->assertSession()->fieldExists('edit-rh-action-display-page');
    $this->assertSession()->fieldExists('edit-rh-action-page-not-found');
    $this->assertSession()->fieldExists('edit-rh-action-page-redirect');
  }

  /**
   * Combines multiple asserts to check that "Rabbit Hole" settings are hidden.
   */
  protected function assertNoRabbitHoleSettings() {
    $this->assertSession()->fieldNotExists('rh_action');
    $this->assertSession()->fieldNotExists('edit-rh-action-access-denied');
    $this->assertSession()->fieldNotExists('edit-rh-action-display-page');
    $this->assertSession()->fieldNotExists('edit-rh-action-page-not-found');
    $this->assertSession()->fieldNotExists('edit-rh-action-page-redirect');
  }

  /**
   * Loads the bundle configuration form.
   */
  protected function loadEntityBundleForm($bundle) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getEditBundleUrl($bundle));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Loads the "Create" entity form.
   */
  protected function loadCreateEntityForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getCreateEntityUrl());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Loads the "Edit" entity form.
   */
  protected function loadEditEntityForm($entity_id) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getEditEntityUrl($entity_id));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Loads test entity.
   *
   * @param mixed $id
   *   ID of loaded entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Loaded entity.
   */
  protected function loadEntity($id) {
    $storage = \Drupal::entityTypeManager()->getStorage($this->entityType);
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

  /**
   * Loads test bundle object.
   *
   * @param mixed $id
   *   Bundle ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityBundleBase
   *   Loaded bundle.
   */
  protected function loadBundle($id) {
    $storage = \Drupal::entityTypeManager()->getStorage($this->bundleEntityTypeName);
    $storage->resetCache([$id]);
    $bundle = $storage->load($id);
    $this->assertInstanceOf(ConfigEntityBundleBase::class, $bundle);
    return $bundle;
  }

  /**
   * Formats selector of the action input.
   *
   * @param string $action
   *   Rabbit hole action.
   *
   * @return string
   *   Selector for the given behavior option.
   */
  protected function getOptionId($action) {
    return 'edit-rh-action-' . str_replace('_', '-', $action);
  }

  /**
   * Returns form submit name/identifier for entity create/edit form.
   *
   * @return string
   *   Value of the submit button whose click is to be emulated.
   */
  protected function getEntityFormSubmit() {
    return 'edit-submit';
  }

  /**
   * Returns URL of the "Edit" entity bundle page.
   *
   * @param string $bundle
   *   Entity bundle id.
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  abstract protected function getEditBundleUrl($bundle);

  /**
   * Returns URL of the "Create" entity page.
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  abstract protected function getCreateEntityUrl();

  /**
   * Creates new entity bundle.
   *
   * @return string
   *   ID of the created bundle.
   */
  abstract protected function createEntityBundle();

  /**
   * Creates new entity bundle via form submit.
   */
  abstract protected function createEntityBundleFormSubmit($action, $override);

  /**
   * Creates new entity.
   *
   * @param string $action
   *   Rabbit Hole action.
   *
   * @return int
   *   ID of the created entity.
   */
  abstract protected function createEntity($action = NULL);

  /**
   * Returns a list of admin permissions for current entity type.
   *
   * @return array
   *   A list of admin permissions.
   */
  abstract protected function getAdminPermissions();

}
