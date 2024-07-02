<?php

namespace Drupal\Tests\node_revision_delete\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\NodeInterface;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the node revision delete plugins.
 *
 * @group node_revision_delete
 */
class NodeRevisionDeleteTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'content_translation',
    'field',
    'filter',
    'language',
    'node',
    'node_revision_delete',
    'system',
    'text',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'content_moderation',
      'system',
      'filter',
      'node',
    ]);

    // Add the Dutch language.
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Create a node type that allows revisions.
    $this->createContentType(['type' => 'page', 'revision' => TRUE]);
  }

  /**
   * Test the node revision delete "amount" plugin.
   */
  public function testNodeRevisionDeleteAmount(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Configure the default settings for the "amount" plugin to allow a maximum
    // of 5 revisions.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 revisions.
    $node = $this->createNode(['type' => 'page']);
    for ($i = 0; $i < 10; $i++) {
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that only 5 revisions remain.
    $this->assertCount(5, $node_storage->revisionIds($node));

    // Override the default settings for page node type to allow a maximum of 3
    // revisions.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'amount', [
      'status' => TRUE,
      'settings' => [
        'amount' => 3,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a revision and run the queue.
    $new_revision = $node_storage->createRevision($node);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that only 3 revisions remain.
    $this->assertCount(3, $node_storage->revisionIds($node));
  }

  /**
   * Test the node revision delete "created" plugin.
   */
  public function testNodeRevisionDeleteCreated() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Configure the default settings for the "created" plugin to allow
    // revisions to exist for a maximum of 5 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'created' => [
          'status' => TRUE,
          'settings' => [
            'age' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 revisions, each 31 days newer than the previous.
    $node = $this->createNode([
      'type' => 'page',
      'created' => strtotime('-' . (10 * 31) . ' days'),
      'changed' => strtotime('-' . (10 * 31) . ' days'),
    ]);
    for ($i = 9; $i >= 0; $i--) {
      $node->setChangedTime(strtotime('-' . ($i * 31) . ' days'));
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that only 5 revisions remain. The items created 5 * 31 days ago
    // (and before) should have been deleted.
    $this->assertCount(5, $node_storage->revisionIds($node));

    // Override the default settings for page node type to allow revisions to
    // exist for a maximum of 3 months.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'created', [
      'status' => TRUE,
      'settings' => [
        'age' => 3,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a revision and run the queue.
    $new_revision = $node_storage->createRevision($node);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 4 revisions remain. The latest revision should be kept, and
    // 3 of the revisions that were created previously.
    $this->assertCount(4, $node_storage->revisionIds($node));
  }

  /**
   * Test the node revision delete "drafts" plugin.
   */
  public function testNodeRevisionDeleteDrafts(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Add the editorial workflow for the node type.
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Configure the default settings for the "drafts" plugin to allow draft
    // revisions to exist for a maximum of 5 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'drafts' => [
          'status' => TRUE,
          'settings' => [
            'age' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 draft revisions, each 31 days newer than the previous.
    $node = $this->createNode([
      'type' => 'page',
      'created' => strtotime('-' . (10 * 31) . ' days'),
      'changed' => strtotime('-' . (10 * 31) . ' days'),
      'status' => NodeInterface::PUBLISHED,
      'moderation_state' => 'published',
    ]);
    for ($i = 9; $i >= 0; $i--) {
      $node->set('moderation_state', 'draft');
      $node->setChangedTime(strtotime('-' . ($i * 31) . ' days'));
      $new_revision = $node_storage->createRevision($node, FALSE);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 6 revisions remain. The draft items created 5 * 31 days ago
    // (and before) should have been deleted. The published revision should also
    // be kept.
    $this->assertCount(6, $node_storage->revisionIds($node));

    // Override the default settings for page node type to allow draft revisions
    // to exist for a maximum of 3 months.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'drafts', [
      'status' => TRUE,
      'settings' => [
        'age' => 3,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a draft revision and run the queue.
    $node->set('moderation_state', 'draft');
    $new_revision = $node_storage->createRevision($node, FALSE);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 5 revisions remain. The latest draft revision should be kept,
    // and 3 of the revisions that were created previously. The published
    // revision should also be kept.
    $this->assertCount(5, $node_storage->revisionIds($node));
  }

  /**
   * Test the node revision delete plugin integration.
   */
  public function testNodeRevisionDeleteIntegration(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Add the editorial workflow for the node type.
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Configure the default settings for the plugins to allow a maximum of 2
    // older revisions. Only revisions created 5 months ago (and before) should
    // be kept, and drafts should be kept for 3 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 2,
          ],
        ],
        'created' => [
          'status' => TRUE,
          'settings' => [
            'age' => 5,
          ],
        ],
        'drafts' => [
          'status' => TRUE,
          'settings' => [
            'age' => 4,
          ],
        ],
      ])
      ->save();

    // Create 10 published revisions, each 31 days newer than the previous.
    $node = $this->createNode([
      'type' => 'page',
      'created' => strtotime('-' . (10 * 31) . ' days'),
      'changed' => strtotime('-' . (10 * 31) . ' days'),
      'status' => NodeInterface::PUBLISHED,
      'moderation_state' => 'published',
    ]);
    for ($i = 9; $i >= 0; $i--) {
      $node->setChangedTime(strtotime('-' . ($i * 31) . ' days'));
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 5 revisions remain. The items created 5 * 31 days ago (and
    // before) should have been deleted. Since the "created" plugin prevents
    // deleting some revisions, the "amount" plugin should not have any effect.
    $this->assertCount(5, $node_storage->revisionIds($node));

    // Create 10 draft revisions for the same node, each 31 days newer than the
    // previous.
    for ($i = 9; $i >= 0; $i--) {
      $node->set('moderation_state', 'draft');
      $node->setChangedTime(strtotime('-' . ($i * 31) . ' days'));
      $new_revision = $node_storage->createRevision($node, FALSE);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 9 revisions remain. The draft items created 4 * 31 days ago
    // (and before) should have been deleted. The published revisions should
    // also have been kept.
    $this->assertCount(9, $node_storage->revisionIds($node));

    // Configure the default settings for the plugins to allow a maximum of 4
    // older revisions. Only revisions created 1 month ago (and before) should
    // be kept, and drafts should be kept for 3 months.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 4,
          ],
        ],
        'created' => [
          'status' => TRUE,
          'settings' => [
            'age' => 1,
          ],
        ],
        'drafts' => [
          'status' => TRUE,
          'settings' => [
            'age' => 3,
          ],
        ],
      ])
      ->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a draft revision and run the queue.
    $node->set('moderation_state', 'draft');
    $new_revision = $node_storage->createRevision($node, FALSE);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 8 revisions remain. Since most revisions are more than 1
    // month old, they should be deleted, but the "amount" plugin should prevent
    // deleting the latest 4 published revisions. We previously created 3 draft
    // revisions that are less than 3 months old. They should still be kept. We
    // also just created a new draft revision, which should also be kept.
    $this->assertCount(8, $node_storage->revisionIds($node));

    // Override the default settings for page node type to allow a maximum of 3
    // older revisions. Only revisions created 1 month ago (and before) should
    // be kept, and drafts should be kept for 2 months.
    $node_type = $this->container->get('entity_type.manager')->getStorage('node_type')->load('page');
    $node_type->setThirdPartySetting('node_revision_delete', 'amount', [
      'status' => TRUE,
      'settings' => [
        'amount' => 3,
      ],
    ]);
    $node_type->setThirdPartySetting('node_revision_delete', 'created', [
      'status' => TRUE,
      'settings' => [
        'age' => 1,
      ],
    ]);
    $node_type->setThirdPartySetting('node_revision_delete', 'drafts', [
      'status' => TRUE,
      'settings' => [
        'age' => 2,
      ],
    ]);
    $node_type->save();
    $this->container->get('plugin.manager.node_revision_delete')->resetCache();

    // Add a draft revision and run the queue.
    $node->set('moderation_state', 'draft');
    $new_revision = $node_storage->createRevision($node, FALSE);
    $new_revision->save();
    $this->runNodeRevisionDeleteQueue();

    // Assert that 7 revisions remain. Since most revisions are more than 1
    // month old, they should be deleted, but the "amount" plugin should prevent
    // deleting the latest 3 published revisions. We previously created 2 draft
    // revisions that are less than 2 months old. They should still be kept. We
    // also created 2 new draft revisions separately, which should also be kept.
    $this->assertCount(7, $node_storage->revisionIds($node));
  }

  /**
   * Test the node revision delete for multilingual content.
   */
  public function testNodeRevisionDeleteMultilanguage(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Configure the default settings for the "amount" plugin to allow a maximum
    // of 5 revisions.
    $this->config('node_revision_delete.settings')
      ->set('defaults', [
        'amount' => [
          'status' => TRUE,
          'settings' => [
            'amount' => 5,
          ],
        ],
      ])
      ->save();

    // Create 10 revisions.
    $node = $this->createNode(['type' => 'page']);
    for ($i = 0; $i < 10; $i++) {
      $new_revision = $node_storage->createRevision($node);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that only 5 revisions remain.
    $this->assertCount(5, $node_storage->revisionIds($node));

    // Translate the node to Dutch and create 10 more revisions.
    $translation = $node->addTranslation('nl', ['title' => 'Dutch title']);
    for ($i = 0; $i < 10; $i++) {
      $new_revision = $node_storage->createRevision($translation);
      $new_revision->save();
      $this->runNodeRevisionDeleteQueue();
    }

    // Assert that 10 revisions remain. There should be 5 Dutch revisions, and 5
    // English revisions.
    $this->assertCount(10, $node_storage->revisionIds($node));
  }

  /**
   * Runs the node revision delete queue for the test.
   */
  protected function runNodeRevisionDeleteQueue(): void {
    $queue = $this->container->get('queue')->get('node_revision_delete');
    $queue_worker = $this->container->get('plugin.manager.queue_worker')->createInstance('node_revision_delete');

    // Assert a queue item has been created.
    $this->assertEquals(1, $this->container->get('queue')->get('node_revision_delete')->numberOfItems());

    // Run the queue to allow revisions to be deleted.
    while ($item = $queue->claimItem()) {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }

    // Assert the queue item has been processed.
    $this->assertEquals(0, $this->container->get('queue')->get('node_revision_delete')->numberOfItems());
  }

}
