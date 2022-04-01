<?php

namespace Drupal\Tests\migrate_example\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests migrate_example migrations.
 *
 * @group migrate_plus
 */
class MigrateExampleTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'comment',
    'text',
    'migrate_plus',
    'migrate_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'node',
      'comment',
      'migrate_example',
    ]);

    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);

    // Install the module via installer to trigger hook_install.
    \Drupal::service('module_installer')->install(['migrate_example_setup']);
    $this->installConfig(['migrate_example_setup']);

    // Execute "beer" migrations from 'migrate_example' module.
    $this->executeMigrations([
      'beer_user',
      'beer_term',
      'beer_node',
      'beer_comment',
    ]);
  }

  /**
   * Tests the results of "Beer" example migration.
   */
  public function testBeerMigration() {
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    // There are 4 users created in beer_user migration and 1 stub entity
    // created during beer_node migration.
    $this->assertCount(5, $users);

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(3, $terms);

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    $this->assertCount(3, $nodes);

    $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadMultiple();
    $this->assertCount(5, $comments);
  }

}
