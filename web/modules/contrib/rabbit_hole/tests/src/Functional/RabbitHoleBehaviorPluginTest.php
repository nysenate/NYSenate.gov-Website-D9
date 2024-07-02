<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\AccessDenied;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\DisplayPage;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageNotFound;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageRedirect;

/**
 * Test the functionality of the RabbitHoleBehavior plugin.
 *
 * @group rabbit_hole
 */
class RabbitHoleBehaviorPluginTest extends BrowserTestBase {
  const TEST_CONTENT_TYPE_ID = 'rh_test_content_type';
  const TEST_NODE_NAME = 'rh_test_node';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rabbit_hole', 'node'];

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $manager;

  /**
   * An entity to test with.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin');

    // Create a content type and entity to test with.
    $this->createTestContentType();
    $this->entity = $this->createTestEntity();
  }

  /**
   * Test the plugin manager.
   */
  public function testPluginManager() {
    // Check that we can get a behavior plugin.
    $this->assertNotNull($this->manager, 'Drupal plugin service returned a rabbit hole behavior service.');

    // Check that the behavior plugin manager is the type we expect.
    $this->assertInstanceOf(RabbitHoleBehaviorPluginManager::class, $this->manager);

    // Check the rabbit_hole module defines the expected number of behaviors.
    $behaviors = $this->manager->getDefinitions();
    $this->assertCount(4, $behaviors, 'There are 4 behaviors.');

    // Check that the plugins defined by the rabbit_hole module are in the list
    // of plugins.
    $this->assertTrue($this->manager->hasDefinition('access_denied'), 'There is an access denied plugin');
    $this->assertArrayHasKey('label', $behaviors['access_denied'], 'The access denied plugin has a label');
    $this->assertTrue($this->manager->hasDefinition('display_page'), 'There is a display the page plugin');
    $this->assertArrayHasKey('label', $behaviors['display_page'], 'The display the page plugin has a label');
    $this->assertTrue($this->manager->hasDefinition('page_not_found'), 'There is a page not found plugin');
    $this->assertArrayHasKey('label', $behaviors['page_not_found'], 'The page not found plugin has a label');
    $this->assertTrue($this->manager->hasDefinition('page_redirect'), 'There is a page redirect plugin');
    $this->assertArrayHasKey('label', $behaviors['page_redirect'], 'The page redirect plugin has a label');
  }

  /**
   * Test the access denied plugin.
   */
  public function testAccessDeniedPlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('access_denied', ['of' => 'configuration values']);
    $this->assertInstanceOf(AccessDenied::class, $plugin, 'The access denied plugin is the correct type.');

    // Check that the plugin performs the expected action.
    $this->expectException(AccessDeniedHttpException::class);
    $plugin->performAction($this->entity);
  }

  /**
   * Test the display page plugin.
   */
  public function testDisplayPagePlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('display_page', ['of' => 'configuration values']);
    $this->assertInstanceOf(DisplayPage::class, $plugin, 'The display page plugin is the correct type.');

    // Check that the plugin performs the expected action.
    $this->assertEmpty($plugin->performAction($this->entity));
  }

  /**
   * Test the page not found plugin.
   */
  public function testPageNotFoundPlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('page_not_found', ['of' => 'configuration values']);
    $this->assertInstanceOf(PageNotFound::class, $plugin, 'The page not found plugin is the correct type.');

    // Check that the plugin performs the expected action.
    $this->expectException(NotFoundHttpException::class);
    $plugin->performAction($this->entity);
  }

  /**
   * Test the page redirect plugin to the frontpage.
   */
  public function testPageRedirectPlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('page_redirect', ['of' => 'configuration values']);
    $this->assertInstanceOf(PageRedirect::class, $plugin, 'The page redirect plugin is the correct type.');

    // Check that the plugin performs the expected action.
    // TODO: Check that $plugin->performAction() does what it's supposed to,
    // whatever that is.
  }

  /**
   * Create a content type for testing.
   *
   * @return \Drupal\node\NodeTypeInterface
   *   Content type entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestContentType() {
    $node_type = NodeType::create(
      [
        'type' => self::TEST_CONTENT_TYPE_ID,
        'name' => self::TEST_CONTENT_TYPE_ID,
      ]
    );
    $node_type->save();

    return $node_type;
  }

  /**
   * Create an entity for testing.
   *
   * @return \Drupal\node\NodeInterface
   *   Created node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestEntity() {
    $node = Node::create(
      [
        'nid' => NULL,
        'type' => self::TEST_CONTENT_TYPE_ID,
        'title' => 'Test Behavior Settings Node',
      ]
    );
    $node->save();

    return $node;
  }

}
