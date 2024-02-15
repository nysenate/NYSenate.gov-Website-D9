<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests Scheduler with Workbench Moderation installed.
 *
 * @group scheduler
 */
class SchedulerWorkbenchModerationTest extends SchedulerBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // This test class is "optional" and will be run if the workbench_moderation
    // modules are available. This allows testing with Drupal 9 but also will
    // not fail with Drupal 10, where the workbench moderation modules are not
    // compatible. See https://www.drupal.org/project/scheduler/issues/3314267
    $modulesList = \Drupal::service('extension.list.module')->getList();
    if (!isset($modulesList['workbench_moderation']) || !isset($modulesList['workbench_moderation_actions'])) {
      $this->markTestSkipped('Skipping test because the workbench moderation module(s) are not available.');
    }
    else {
      // The workbench_moderation module is available so install it.
      // workbench_moderation_actions is installed later.
      \Drupal::service('module_installer')->install(['workbench_moderation']);
    }
  }

  /**
   * Helper function to test publishing and unpublishing via cron.
   */
  public function schedulingWithWorkbenchModeration($type) {
    $this->drupalLogin($this->schedulerUser);

    // Create a node that is scheduled for publishing.
    $settings = [
      'publish_on' => strtotime('-1 day'),
      'status' => FALSE,
      'type' => $type,
      'title' => "{$type} for publishing",
    ];
    $node = $this->drupalCreateNode($settings);

    // Run cron and check that the node has been published successfully.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), "The node should be published after cron");

    // Set a date for unpublishing the node.
    $node->set('unpublish_on', strtotime('-1 day'))->save();

    // Run cron and check that the node has been unpublished successfully.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertFalse($node->isPublished(), "The node should be unpublished after cron");
  }

  /**
   * Test when only workbench_moderation is installed.
   */
  public function testWorkbenchModerationOnly() {
    // Test with a node type that is not included in a moderation workflow.
    $this->schedulingWithWorkbenchModeration($this->type);
  }

  /**
   * Test when workbench_moderation_actions is also installed.
   */
  public function testWorkbenchModerationWithWorkbenchModerationActions() {
    // Install workbench_moderation_actions and run the same test as above.
    \Drupal::service('module_installer')->install(['workbench_moderation_actions']);
    $this->schedulingWithWorkbenchModeration($this->type);
  }

}
