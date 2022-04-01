<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests a content type which is not enabled for scheduling.
 *
 * @group scheduler
 */
class SchedulerNonEnabledTypeTest extends SchedulerBrowserTestBase {

  /**
   * Tests the publish_enable and unpublish_enable node type settings.
   *
   * @dataProvider dataNonEnabledType()
   */
  public function testNonEnabledType($id, $description, $publishing_enabled, $unpublishing_enabled) {
    $this->drupalLogin($this->adminUser);

    // The first test case specifically checks the behavior of the default
    // unchanged settings, so only change these settings for later runs.
    if ($id > 0) {
      $this->nonSchedulerNodeType->setThirdPartySetting('scheduler', 'publish_enable', $publishing_enabled)
        ->setThirdPartySetting('scheduler', 'unpublish_enable', $unpublishing_enabled)
        ->save();
    }

    // Create info string to show what combinations are being tested.
    $info = 'Publishing ' . ($publishing_enabled ? 'enabled' : 'not enabled')
      . ', Unpublishing ' . ($unpublishing_enabled ? 'enabled' : 'not enabled')
      . ', ' . $description;

    // Check that the field(s) are displayed only for the correct settings.
    $title = $id . 'a - ' . $info;
    $this->drupalGet('node/add/' . $this->nonSchedulerNodeType->id());
    if ($publishing_enabled) {
      $this->assertSession()->fieldExists('publish_on[0][value][date]');
    }
    else {
      $this->assertSession()->fieldNotExists('publish_on[0][value][date]');
    }

    if ($unpublishing_enabled) {
      $this->assertSession()->fieldExists('unpublish_on[0][value][date]');
    }
    else {
      $this->assertSession()->fieldNotExists('unpublish_on[0][value][date]');
    }

    // When publishing and/or unpublishing are not enabled but the 'required'
    // setting remains on, the node must be able to be saved without a date.
    $this->nonSchedulerNodeType->setThirdPartySetting('scheduler', 'publish_required', !$publishing_enabled)->save();
    $this->nonSchedulerNodeType->setThirdPartySetting('scheduler', 'unpublish_required', !$unpublishing_enabled)->save();
    $this->drupalPostForm('node/add/' . $this->nonSchedulerNodeType->id(), ['title[0][value]' => $title], 'Save');
    // Check that the node has saved OK.
    $string = sprintf('%s %s has been created.', $this->nonSchedulerNodeType->get('name'), $title);
    $this->assertSession()->pageTextContains($string);

    // Create an unpublished node with a publishing date, which mimics what
    // could be done by a third-party module, or a by-product of the node type
    // being enabled for publishing then being disabled before it got published.
    $title = $id . 'b - ' . $info;
    $edit = [
      'title' => $title,
      'status' => 0,
      'type' => $this->nonSchedulerNodeType->id(),
      'publish_on' => $this->requestTime - 2,
    ];
    $node = $this->drupalCreateNode($edit);

    // Run cron and display the dblog.
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');

    // Reload the node.
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check if the node has been published or remains unpublished.
    if ($publishing_enabled) {
      $this->assertTrue($node->isPublished(), 'The unpublished node has been published - ' . $title);
    }
    else {
      $this->assertFalse($node->isPublished(), 'The unpublished node remains unpublished - ' . $title);
    }

    // Do the same for unpublishing.
    $title = $id . 'c - ' . $info;
    $edit = [
      'title' => $title,
      'status' => 1,
      'type' => $this->nonSchedulerNodeType->id(),
      'unpublish_on' => $this->requestTime - 1,
    ];
    $node = $this->drupalCreateNode($edit);

    // Run cron and display the dblog.
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');

    // Reload the node.
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check if the node has been unpublished or remains published.
    if ($unpublishing_enabled) {
      $this->assertFalse($node->isPublished(), 'The published node has been unpublished - ' . $title);
    }
    else {
      $this->assertTrue($node->isPublished(), 'The published node remains published - ' . $title);
    }

    // Display the full content list and the scheduled list. Calls to these
    // pages are for information and debug only. They could be removed.
    $this->drupalGet('admin/content');
    $this->drupalGet('admin/content/scheduled');
  }

  /**
   * Provides data for testNonEnabledType().
   *
   * @return array
   *   Each item in the test data array has the follow elements:
   *     id                   - (in) a sequential id for use in node titles
   *     description          - (string) describing the scenario being checked
   *     publishing_enabled   - (bool) whether publishing is enabled
   *     unpublishing_enabled - (bool) whether unpublishing is enabled
   */
  public function dataNonEnabledType() {
    $data = [
      // By default check that the scheduler date fields are not displayed.
      0 => [0, 'Default', FALSE, FALSE],

      // Explicitly disable this content type for both settings.
      1 => [1, 'Disabling both settings', FALSE, FALSE],

      // Turn on scheduled publishing only.
      2 => [2, 'Enabling publishing only', TRUE, FALSE],

      // Turn on scheduled unpublishing only.
      3 => [3, 'Enabling unpublishing only', FALSE, TRUE],

      // For completeness turn on bothbscheduled publishing and unpublishing.
      4 => [4, 'Enabling both publishing and unpublishing', TRUE, TRUE],
    ];

    // Use unset($data[n]) to remove a temporarily unwanted item, use
    // return [$data[n]] to selectively test just one item, or have the
    // default return $data to test everything.
    return $data;

  }

}
