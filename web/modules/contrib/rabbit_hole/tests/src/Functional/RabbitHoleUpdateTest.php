<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Tests Rabbit Hole update path from 8.x-1.x to 2.0.x.
 *
 * @group rabbit_hole
 */
class RabbitHoleUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    'rabbit_hole.behavior_settings.taxonomy_term.tags',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/drupal-9.5.standard.rabbit_hole.php',
    ];
  }

  /**
   * Tests the update path for configuration objects.
   */
  public function testConfigUpdatePath() {
    \Drupal::service('module_installer')->install(['rabbit_hole_test_custom_actions']);

    // Check configs before update.
    $article_settings = \Drupal::config('rabbit_hole.behavior_settings.node_type_article')->getRawData();
    $this->assertEquals('/articles', $article_settings['redirect']);
    $this->assertEquals(301, $article_settings['redirect_code']);
    $this->assertEquals('access_denied', $article_settings['redirect_fallback_action']);
    $this->assertArrayNotHasKey('configuration', $article_settings);

    $this->runUpdates();

    // Verify updated configs.
    // Action: redirect, redirect configuration should be migrated.
    $article_settings = \Drupal::config('rabbit_hole.behavior_settings.node.article')->getRawData();
    $this->assertArrayNotHasKey('redirect', $article_settings);
    $this->assertArrayNotHasKey('redirect_code', $article_settings);
    $this->assertArrayNotHasKey('redirect_fallback_action', $article_settings);
    $this->assertEquals('/articles', $article_settings['configuration']['redirect']);
    $this->assertEquals(301, $article_settings['configuration']['redirect_code']);
    $this->assertEquals('access_denied', $article_settings['configuration']['redirect_fallback_action']);

    // Action: access denied, redirect configuration should be removed.
    $user_settings = \Drupal::config('rabbit_hole.behavior_settings.user.user')->getRawData();
    $this->assertEmpty($user_settings['configuration']);

    // Action: custom redirect, redirect configuration should be migrated.
    $tags_settings = \Drupal::config('rabbit_hole.behavior_settings.taxonomy_term.tags')->getRawData();
    $this->assertArrayNotHasKey('redirect', $tags_settings);
    $this->assertArrayNotHasKey('redirect_code', $tags_settings);
    $this->assertArrayNotHasKey('redirect_fallback_action', $tags_settings);
    $this->assertEquals('/tags', $tags_settings['configuration']['redirect']);
    $this->assertEquals(301, $tags_settings['configuration']['redirect_code']);
    $this->assertEquals('access_denied', $tags_settings['configuration']['redirect_fallback_action']);

    // Make sure settings do not have "allow_override" property.
    $this->assertArrayNotHasKey('allow_override', $user_settings);
    $this->assertArrayNotHasKey('allow_override', $tags_settings);
  }

  /**
   * Tests the update path for base fields.
   */
  public function testFieldValuesUpdate() {
    \Drupal::service('module_installer')->install(['rabbit_hole_test_custom_actions']);

    $this->runUpdates();

    // Bundle default action, no configuration expected.
    $node1 = Node::load(1);
    $this->assertEquals('bundle_default', $node1->get('rabbit_hole__settings')->action);
    $this->assertEmpty($node1->get('rabbit_hole__settings')->settings);
    // Check translation - Rabbit Hole field was translated.
    $this->assertEquals('page_not_found', $node1->getTranslation('uk')->get('rabbit_hole__settings')->action);

    // Bundle settings of this node is not available in the database fixture,
    // so "rabbit_hole.behavior_settings.default" config is used. Verify that
    // action was copied over.
    $node2 = Node::load(2);
    $this->assertEquals('page_not_found', $node2->get('rabbit_hole__settings')->action);
    $this->assertEmpty($node2->get('rabbit_hole__settings')->settings);

    // Page redirect action, configuration should be migrated.
    $node4 = Node::load(4);
    $this->assertEquals('page_redirect', $node4->get('rabbit_hole__settings')->action);
    $this->assertEquals([
      'redirect' => '[custom:articles]',
      'redirect_code' => '301',
      'redirect_fallback_action' => 'bundle_default',
    ], $node4->get('rabbit_hole__settings')->settings);
    // Check translation - Rabbit Hole field was not translated, so value should
    // be the same.
    $this->assertEquals('page_redirect', $node4->getTranslation('uk')->get('rabbit_hole__settings')->action);

    // Custom action extended from "Page redirect", configuration should be
    // migrated.
    $node4 = Node::load(10);
    $this->assertEquals('page_redirect_custom', $node4->get('rabbit_hole__settings')->action);
    $this->assertEquals([
      'redirect' => '<front>',
      'redirect_code' => '301',
      'redirect_fallback_action' => 'bundle_default',
    ], $node4->get('rabbit_hole__settings')->settings);

    $node5 = Node::load(5);
    $this->assertEquals('page_not_found', $node5->get('rabbit_hole__settings')->action);
    $this->assertEmpty($node5->get('rabbit_hole__settings')->settings);

    // Make sure taxonomy terms are covered.
    $term1 = Term::load(1);
    $this->assertEquals('page_not_found', $term1->get('rabbit_hole__settings')->action);
    $this->assertEmpty($term1->get('rabbit_hole__settings')->settings);

    $term4 = Term::load(4);
    $this->assertEquals('page_redirect', $term4->get('rabbit_hole__settings')->action);
    $this->assertEquals([
      'redirect' => '/tags',
      'redirect_code' => '301',
      'redirect_fallback_action' => 'page_not_found',
    ], $term4->get('rabbit_hole__settings')->settings);

    // Make sure user entities are covered.
    $user2 = User::load(2);
    $this->assertEquals('access_denied', $user2->get('rabbit_hole__settings')->action);
    $this->assertEmpty($user2->get('rabbit_hole__settings')->settings);
  }

}
