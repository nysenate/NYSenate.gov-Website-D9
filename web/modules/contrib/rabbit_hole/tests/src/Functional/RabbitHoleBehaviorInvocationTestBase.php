<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the rabbit hole behaviors invocation tests.
 */
abstract class RabbitHoleBehaviorInvocationTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole'];

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
    $this->drupalLogin($this->drupalCreateUser($this->getViewPermissions()));
  }

  /**
   * Test that a fresh entity with a fresh bundle takes the default action.
   */
  public function testEntityDefaults() {
    $this->createEntityBundle();
    $entity = $this->createEntity();
    $this->drupalGet($entity->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test action not set or set to bundle_default will default to bundle action.
   */
  public function testDefaultToBundle() {
    $this->createEntityBundle('access_denied');

    $entity = $this->createEntity();
    $this->drupalGet($entity->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);

    $entity2 = $this->createEntity('bundle_default');
    $this->drupalGet($entity2->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test that an entity set to page_not_found overrides returns a 404.
   */
  public function testPageNotFound() {
    $this->createEntityBundle();
    $entity = $this->createEntity('page_not_found');
    $this->drupalGet($entity->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test that an entity set to access_denied returns a 403 response.
   */
  public function testAccessDenied() {
    $this->createEntityBundle();
    $entity = $this->createEntity('access_denied');
    $this->drupalGet($entity->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test that an entity set to display_page returns a 200 response.
   */
  public function testDisplayPage() {
    $this->createEntityBundle('access_denied');
    $entity = $this->createEntity('display_page');
    $this->drupalGet($entity->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test that entity set to page_redirect actually redirects.
   */
  public function testPageRedirect() {
    $this->createEntityBundle();
    $destination_path = $this->createEntity('display_page')
      ->toUrl('canonical', ['absolute' => TRUE])
      ->toString();

    $entity = $this->createEntity('page_redirect');
    $entity->set('rh_redirect', $destination_path);
    $entity->set('rh_redirect_response', 301);
    $entity->save();

    $this->drupalGet($entity->toUrl()->toString());
    $this->assertSession()->addressEquals($destination_path);
  }

  /**
   * Test "rabbit hole bypass *" permissions.
   */
  public function testRabbitHoleBypassPermissions() {
    $this->drupalLogin($this->createUser(array_merge($this->getViewPermissions(), ['rabbit hole bypass ' . $this->entityType])));
    $this->createEntityBundle();
    $entity = $this->createEntity('page_not_found');
    $this->drupalGet($entity->toUrl()->toString());

    // Users with bypass permission should have access to entity pages.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Creates new entity bundle.
   *
   * @param string $action
   *   Rabbit Hole action.
   *
   * @return string
   *   ID of the created bundle.
   */
  abstract protected function createEntityBundle($action = NULL);

  /**
   * Creates new entity.
   *
   * @param string $action
   *   Rabbit Hole action.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created entity.
   */
  abstract protected function createEntity($action = NULL);

  /**
   * A list of permissions required to access the entity page.
   *
   * @return array
   *   A list of permissions.
   */
  protected function getViewPermissions() {
    return [];
  }

}
