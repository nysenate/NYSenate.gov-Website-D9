<?php

namespace Drupal\Tests\entity_usage\FunctionalJavascript;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Tests for the batch update functionality.
 *
 * @package Drupal\Tests\entity_usage\FunctionalJavascript
 *
 * @group entity_usage
 */
class BatchUpdateTest extends EntityUsageJavascriptTestBase {

  /**
   * Tests the batch update.
   */
  public function testBatchUpdate() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // No permissions, you get a 403 when trying to access the batch update.
    $this->drupalGet('/admin/config/entity-usage/batch-update');
    $assert_session->pageTextContains('You are not authorized to access this page');
    // Grant the logged-in the needed permission and try again.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['perform batch updates entity usage']);
    $this->drupalGet('/admin/config/entity-usage/batch-update');
    $assert_session->pageTextContains('Batch update');
    $assert_session->pageTextContains('This page allows you to delete and re-generate again all entity usage statistics in your system');

    /** @var \Drupal\entity_usage\EntityUsage $usage_service */
    $usage_service = \Drupal::service('entity_usage.usage');

    // Create node 1.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 1');
    $page->pressButton('Save');
    $session->wait(500);
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('eu_test_ct Node 1 has been created.');
    $node1 = Node::load(1);

    // Create node 2 referencing node 1 using reference field.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 2');
    $page->fillField('field_eu_test_related_nodes[0][target_id]', 'Node 1 (1)');
    $page->pressButton('Save');
    $session->wait(500);
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('eu_test_ct Node 2 has been created.');

    // Create node 3 also referencing node 1 in a reference field.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 3');
    $page->fillField('field_eu_test_related_nodes[0][target_id]', 'Node 1 (1)');
    $page->pressButton('Save');
    $session->wait(500);
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('eu_test_ct Node 3 has been created.');

    // Remove one of the records from the database to simulate an usage
    // non-tracked by the module.
    $usage_service->deleteBySourceEntity(2, 'node');
    $usage = $usage_service->listSources($node1);
    $this->assertEquals($usage['node'], [
      '3' => [
        0 => [
          'source_langcode' => 'en',
          'source_vid' => '3',
          'method' => 'entity_reference',
          'field_name' => 'field_eu_test_related_nodes',
          'count' => 1,
        ],
      ],
    ]);

    // Go to the batch update page and check the update.
    $this->drupalGet('/admin/config/entity-usage/batch-update');
    $assert_session->pageTextContains('Batch Update');
    $assert_session->pageTextContains('This page allows you to delete and re-generate again all entity usage statistics in your system.');
    $assert_session->pageTextContains('You may want to check the settings page to fine-tune what entities should be tracked, and other options.');

    // If in the settings form we have disabled tracking for nodes, the batch
    // update should have no effect.
    $config = \Drupal::configFactory()->getEditable('entity_usage.settings');
    $config->set('track_enabled_source_entity_types', []);
    $config->save();

    $page->pressButton('Recreate all entity usage statistics');
    $this->getSession()->wait(1000);
    $this->saveHtmlOutput();
    $this->getSession()->wait(6000);
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('Recreated entity usage for');

    // The usage is still what we had before.
    $usage = $usage_service->listSources($node1);
    $this->assertEquals($usage['node'], [
      '3' => [
        0 => [
          'source_langcode' => 'en',
          'source_vid' => '3',
          'method' => 'entity_reference',
          'field_name' => 'field_eu_test_related_nodes',
          'count' => 1,
        ],
      ],
    ]);

    // Enable tracking for source nodes and try again.
    $config = \Drupal::configFactory()->getEditable('entity_usage.settings');
    $config->set('track_enabled_source_entity_types', ['node']);
    $config->save();
    $this->drupalGet('/admin/config/entity-usage/batch-update');
    $page->pressButton('Recreate all entity usage statistics');
    $this->getSession()->wait(1000);
    $this->saveHtmlOutput();
    $this->getSession()->wait(6000);
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('Recreated entity usage for');

    // Check if the resulting usage is the expected.
    $usage = $usage_service->listSources($node1);
    $this->assertEquals($usage['node'], [
      '3' => [
        0 => [
          'source_langcode' => 'en',
          'source_vid' => '3',
          'method' => 'entity_reference',
          'field_name' => 'field_eu_test_related_nodes',
          'count' => 1,
        ],
      ],
      '2' => [
        0 => [
          'source_langcode' => 'en',
          'source_vid' => '2',
          'method' => 'entity_reference',
          'field_name' => 'field_eu_test_related_nodes',
          'count' => 1,
        ],
      ],
    ]);
  }

}
