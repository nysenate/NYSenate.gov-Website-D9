<?php

namespace Drupal\Tests\rabbit_hole\Kernel;

use Drupal\Core\DrupalKernel;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageNotFound;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test cases for BehaviorInvoker.
 *
 * @coversDefaultClass \Drupal\rabbit_hole\BehaviorInvoker
 * @group rabbit_hole
 */
class BehaviorInvokerTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'filter',
    'text',
    'field',
    'user',
    'node',
    'rabbit_hole',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['filter', 'node', 'system', 'rabbit_hole']);

    \Drupal::service('rabbit_hole.behavior_settings_manager')->enableEntityType('node');

    $this->createContentType(['type' => 'article']);
    $this->createContentType(['type' => 'page']);

    BehaviorSettings::loadByEntityTypeBundle('node', 'article')
      ->setAction('page_not_found')
      ->setNoBypass(FALSE)
      ->save();

    BehaviorSettings::loadByEntityTypeBundle('node', 'page')
      ->setAction('page_not_found')
      ->setNoBypass(TRUE)
      ->save();
  }

  /**
   * @covers ::getBehaviorPlugin()
   */
  public function testGetBehaviorPlugin() {
    $node1 = Node::create(['title' => '#freeAzov', 'type' => 'article']);
    $node1->save();
    $node2 = Node::create(['title' => '#standWithUkraine', 'type' => 'page']);
    $node2->save();

    $behavior_invoker = \Drupal::service('rabbit_hole.behavior_invoker');

    $this->setUpCurrentUser([], [], TRUE);
    // "No bypass" for articles is disabled, so admin should see the page.
    // In other words, the plugin should be not available.
    $this->assertNull($behavior_invoker->getBehaviorPlugin($node1));
    // For pages, "no bypass" is enabled, so action plugin is expected.
    $this->assertInstanceOf(PageNotFound::class, $behavior_invoker->getBehaviorPlugin($node2));

    // Verify that regular user cannot access article page.
    $this->setUpCurrentUser();
    $this->assertInstanceOf(PageNotFound::class, $behavior_invoker->getBehaviorPlugin($node1));
  }

  /**
   * @covers ::getEntity()
   */
  public function testGetEntity() {
    $behavior_invoker = \Drupal::service('rabbit_hole.behavior_invoker');
    $class_loader = require $this->root . '/autoload.php';
    $kernel = new DrupalKernel('testing', $class_loader, FALSE);

    // Supported, not enabled.
    User::create(['name' => 'borisjohnsonuk'])->save();
    $event = new RequestEvent($kernel, $this->createRequest('/user/1'), HttpKernelInterface::MASTER_REQUEST);
    $this->assertEquals(FALSE, $behavior_invoker->getEntity($event));

    // Enabled.
    Node::create(['title' => 'God Save the King', 'type' => 'some'])->save();
    $event = new RequestEvent($kernel, $this->createRequest('/node/1'), HttpKernelInterface::MASTER_REQUEST);
    $this->assertInstanceOf(NodeInterface::class, $behavior_invoker->getEntity($event));
  }

  /**
   * Creates a request object for given path.
   *
   * @param string $uri
   *   A URI or path.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   Created request object.
   */
  protected function createRequest(string $uri): Request {
    $request = Request::create($uri);
    $parameters = \Drupal::service('router.no_access_checks')->matchRequest($request);
    $request->attributes->add($parameters);
    unset($parameters['_route'], $parameters['_controller']);
    $request->attributes->set('_route_params', $parameters);
    return $request;
  }

}
