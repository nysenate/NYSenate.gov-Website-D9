<?php

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests cache tags of entity queues.
 *
 * @group entityqueue
 */
class EntityQueueCacheTagsTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'filter', 'node', 'text', 'user', 'system', 'views', 'entityqueue', 'entityqueue_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_subqueue');
    $this->installEntitySchema('user');

    $this->installConfig(['filter', 'node', 'system', 'entityqueue_test']);

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests the cache tags of a view with a entity queue relationship.
   */
  public function testViewWithRelationship() {
    $nodes = [];

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test article (1)',
    ]);
    $node->save();
    $nodes[] = $node;

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test article (2)',
    ]);
    $node->save();
    $nodes[] = $node;

    $entity_subqueue = EntitySubqueue::load('simple_queue');
    $entity_subqueue->set('items', $nodes);
    $entity_subqueue->save();

    $build = [
      '#type' => 'view',
      '#name' => 'simple_queue_listing',
    ];

    $renderer = $this->container->get('bare_html_page_renderer');
    $response = $renderer->renderBarePage($build, '', 'maintenance_page');

    $this->assertEqualsCanonicalizing([
      'config:entityqueue.entity_queue.simple_queue',
      'config:views.view.simple_queue_listing',
      'entity_subqueue:simple_queue',
      'entity_subqueue_list',
      'node:1',
      'node:2',
      'node_list',
    ], $response->getCacheableMetadata()->getCacheTags());
  }

}
