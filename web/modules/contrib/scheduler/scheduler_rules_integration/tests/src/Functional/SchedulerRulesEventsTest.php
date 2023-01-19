<?php

namespace Drupal\Tests\scheduler_rules_integration\Functional;

use Drupal\rules\Context\ContextConfig;
use Drupal\Tests\scheduler\Functional\SchedulerBrowserTestBase;

/**
 * Tests the six events that Scheduler provides for use in Rules module.
 *
 * phpcs:set Drupal.Arrays.Array lineLimit 140
 *
 * @group scheduler_rules_integration
 */
class SchedulerRulesEventsTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['scheduler_rules_integration'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');

    // Create six reaction rules, one for each event that Scheduler triggers.
    // These rules are all active throughout all of the tests, which makes the
    // tests stronger, because it will show not only that the correct events are
    // triggered in the right places, but also that they are not triggered in
    // the wrong places.
    $rule_data = [
      1 => ['scheduler_new_node_is_scheduled_for_publishing_event', 'A new node is created and is scheduled for publishing.'],
      2 => ['scheduler_existing_node_is_scheduled_for_publishing_event', 'An existing node is saved and is scheduled for publishing.'],
      3 => ['scheduler_has_published_this_node_event', 'Scheduler has published this node during cron.'],
      4 => ['scheduler_new_node_is_scheduled_for_unpublishing_event', 'A new node is created and is scheduled for unpublishing.'],
      5 => ['scheduler_existing_node_is_scheduled_for_unpublishing_event', 'An existing node is saved and is scheduled for unpublishing.'],
      6 => ['scheduler_has_unpublished_this_node_event', 'Scheduler has unpublished this node during cron.'],
    ];
    // PHPCS throws a false-positive 'variable $var is undefined' message when
    // the variable is defined by list( ) syntax. To avoid the unwanted warnings
    // we can wrap the section with codingStandardsIgnoreStart and IgnoreEnd.
    // @see https://www.drupal.org/project/coder/issues/2876245
    // @codingStandardsIgnoreStart
    foreach ($rule_data as $i => list($event_name, $description)) {
      $rule[$i] = $this->expressionManager->createRule();
      $this->message[$i] = 'RULES message ' . $i . '. ' . $description;
      $rule[$i]->addAction('rules_system_message', ContextConfig::create()
        ->setValue('message', $this->message[$i])
        ->setValue('type', 'status')
        );
      $config_entity = $this->rulesStorage->create([
        'id' => 'rule' . $i,
        'events' => [['event_name' => $event_name]],
        'expression' => $rule[$i]->getConfiguration(),
      ]);
      $config_entity->save();
    }
    // @codingStandardsIgnoreEnd

    $this->drupalLogin($this->schedulerUser);
  }

  /**
   * Tests that no events are triggered when there are no scheduling dates.
   */
  public function testRulesEventsNone() {
    $assert = $this->assertSession();

    // Create a node without any scheduled dates, using node/add/ not
    // drupalCreateNode(), and check that no events are triggered.
    $edit = [
      'title[0][value]' => 'Test for no events',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Edit the node and check that no events are triggered.
    $edit = [
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);
  }

  /**
   * Tests the three events related to publishing a node.
   */
  public function testRulesEventsPublish() {
    $assert = $this->assertSession();

    // Create a node with a publish-on date, and check that only event 1 is
    // triggered.
    $edit = [
      'title[0][value]' => 'Create node with publish-on date',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $assert->pageTextContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Edit this node and check that only event 2 is triggered.
    $edit = [
      'title[0][value]' => 'Edit node with publish-on date',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Delay to ensure that the date entered is now in the past so that the node
    // will be processed during cron, and assert that only event 3 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('node');
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);
  }

  /**
   * Tests the three events related to unpublishing a node.
   */
  public function testRulesEventsUnpublish() {
    $assert = $this->assertSession();

    // Create a node with an unpublish-on date, and check that only event 4 is
    // triggered.
    $edit = [
      'title[0][value]' => 'Create node with unpublish-on date',
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 3),
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Edit this node and check that only event 5 is triggered.
    $edit = [
      'title[0][value]' => 'Edit node with unpublish-on date',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Delay to ensure that the date entered is now in the past so that the node
    // will be processed during cron, and assert that only event 6 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('node');
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextContains($this->message[6]);
  }

  /**
   * Tests all six events related to publishing and unpublishing a node.
   */
  public function testRulesEventsBoth() {
    $assert = $this->assertSession();

    // Create a node with both publish-on and unpublish-on dates, and check that
    // both event 1 and event 4 are triggered.
    $edit = [
      'title[0][value]' => 'Create node with both dates',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 4),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 4),
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $assert->pageTextContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextContains($this->message[4]);
    $assert->pageTextNotContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Edit this node and check that events 2 and 5 are triggered.
    $edit = [
      'title[0][value]' => 'Edit node with both dates',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextContains($this->message[2]);
    $assert->pageTextNotContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextContains($this->message[5]);
    $assert->pageTextNotContains($this->message[6]);

    // Delay to ensure that the dates are now in the past so that the node will
    // be processed during cron, and assert that events 3, 5 & 6 are triggered.
    sleep(6);
    $this->cronRun();
    $this->drupalGet('node');
    $assert->pageTextNotContains($this->message[1]);
    $assert->pageTextNotContains($this->message[2]);
    $assert->pageTextContains($this->message[3]);
    $assert->pageTextNotContains($this->message[4]);
    $assert->pageTextContains($this->message[5]);
    $assert->pageTextContains($this->message[6]);

  }

}
