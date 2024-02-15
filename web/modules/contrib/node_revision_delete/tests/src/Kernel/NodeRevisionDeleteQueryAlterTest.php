<?php

namespace Drupal\Tests\node_revision_delete\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests that appropriate query tags are added.
 *
 * @group node_revision_delete
 */
class NodeRevisionDeleteQueryAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node_revision_delete',
    'node_revision_delete_test',
    'node',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installConfig(['node_revision_delete']);

    // Getting the node revision delete service.
    $this->nodeRevisionDelete = $this->container->get('node_revision_delete');
  }

  /**
   * Tests that appropriate tags are added when querying the database.
   */
  public function testNodeRevisionDeleteQueryAlter() {
    // Add article node type.
    $node_type = NodeType::create([
      'type' => 'article',
      'label' => 'Article',
    ]);
    $node_type->save();

    $this->nodeRevisionDelete->saveContentTypeConfig('article', 1, 0, 0);

    // Add node and revisions.
    $revision1 = Node::create([
      'type' => 'article',
      'title' => 'My article',
    ]);
    $revision1->save();
    $revision2 = clone $revision1;
    $revision2->setNewRevision();
    $revision2->save();
    $revision3 = clone $revision2;
    $revision3->setNewRevision();
    $revision3->save();

    $this->setupQueryTagTestHooks();

    $this->nodeRevisionDelete->getCandidatesNodes('article');
    $this->assertQueryTagTestResult('node_revision_delete_test_query_node_revision_delete_candidates_alter', 1);
    $this->assertQueryTagTestResult('node_revision_delete_test_query_node_revision_delete_candidates_article_alter', 1);

    $this->nodeRevisionDelete->getCandidatesRevisions('article');
    $this->assertQueryTagTestResult('node_revision_delete_test_query_node_revision_delete_candidate_revisions_alter', 1);
    $this->assertQueryTagTestResult('node_revision_delete_test_query_node_revision_delete_candidate_revisions_article_alter', 1);

    $this->nodeRevisionDelete->getCandidatesRevisionsByNids([$revision1->id()]);
    $this->assertQueryTagTestResult('node_revision_delete_test_query_node_revision_delete_candidate_revisions_alter', 2);
    $this->assertQueryTagTestResult('node_revision_delete_test_query_node_revision_delete_candidate_revisions_article_alter', 2);
  }

  /**
   * Sets up the hooks in the test module.
   */
  protected function setupQueryTagTestHooks() {
    $state = $this->container->get('state');
    $state->set('node_revision_delete_test_query_node_revision_delete_candidates_alter', 0);
    $state->set('node_revision_delete_test_query_node_revision_delete_candidates_article_alter', 0);
    $state->set('node_revision_delete_test_query_node_revision_delete_candidate_revisions_alter', 0);
    $state->set('node_revision_delete_test_query_node_revision_delete_candidate_revisions_article_alter', 0);
  }

  /**
   * Verifies invocation of the hooks in the test module.
   *
   * @param string $function
   *   The name of the query alter hook function.
   * @param int $expected_invocations
   *   The number of times the hook is expected to
   *   have been invoked.
   */
  protected function assertQueryTagTestResult($function, $expected_invocations) {
    $state = $this->container->get('state');
    $this->assertEquals($expected_invocations, $state->get($function));
  }

}
