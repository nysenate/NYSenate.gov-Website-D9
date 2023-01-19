<?php

namespace Drupal\Tests\scheduler_rules_integration\Functional;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\rules\Context\ContextConfig;
use Drupal\Tests\scheduler\Functional\SchedulerBrowserTestBase;

/**
 * Tests the six actions that Scheduler provides for use in Rules module.
 *
 * @group scheduler_rules_integration
 */
class SchedulerRulesActionsTest extends SchedulerBrowserTestBase {

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
    // Login as adminUser so that we can also test the non-enabled node type.
    $this->drupalLogin($this->adminUser);

    // Create node A which is published and enabled for Scheduling.
    $this->node_a = $this->drupalCreateNode([
      'title' => 'Initial Test Node',
      'type' => $this->type,
      'uid' => $this->adminUser->id(),
      'status' => TRUE,
    ]);

    // Create node B which is published but not enabled for Scheduling.
    $this->node_b = $this->drupalCreateNode([
      'title' => 'Something Else',
      'type' => $this->nonSchedulerNodeType->id(),
      'uid' => $this->adminUser->id(),
      'status' => TRUE,
    ]);
  }

  /**
   * Tests the actions which set and remove the 'Publish On' date.
   */
  public function testPublishOnActions() {

    $publish_on = $this->requestTime + 1800;
    $publish_on_formatted = $this->dateFormatter->format($publish_on, 'long');

    // Create rule 1 to set the publishing date.
    $rule1 = $this->expressionManager->createRule();
    $rule1->addCondition('rules_data_comparison',
        ContextConfig::create()
          ->map('data', 'node.title.value')
          ->setValue('operation', 'contains')
          ->setValue('value', 'Trigger Action Rule 1')
    );
    $message1 = 'RULES message 1. Action to set Publish-on date.';
    $rule1->addAction('scheduler_set_publishing_date_action',
      ContextConfig::create()
        ->map('node', 'node')
        ->setValue('date', $publish_on)
      )
      ->addAction('rules_system_message',
        ContextConfig::create()
          ->setValue('message', $message1)
          ->setValue('type', 'status')
    );
    // The event needs to be rules_entity_presave:node 'before saving' because
    // rules_entity_update:node 'after save' is too late to set the date.
    $config_entity = $this->rulesStorage->create([
      'id' => 'rule1',
      'events' => [['event_name' => 'rules_entity_presave:node']],
      'expression' => $rule1->getConfiguration(),
    ]);
    $config_entity->save();

    // Create rule 2 to remove the publishing date and publish the node.
    $rule2 = $this->expressionManager->createRule();
    $rule2->addCondition('rules_data_comparison',
        ContextConfig::create()
          ->map('data', 'node.title.value')
          ->setValue('operation', 'contains')
          ->setValue('value', 'Trigger Action Rule 2')
    );
    $message2 = 'RULES message 2. Action to remove Publish-on date and publish the node immediately.';
    $rule2->addAction('scheduler_remove_publishing_date_action',
      ContextConfig::create()
        ->map('node', 'node')
      )
      ->addAction('scheduler_publish_now_action',
        ContextConfig::create()
          ->map('node', 'node')
      )
      ->addAction('rules_system_message',
        ContextConfig::create()
          ->setValue('message', $message2)
          ->setValue('type', 'status')
      );
    $config_entity = $this->rulesStorage->create([
      'id' => 'rule2',
      'events' => [['event_name' => 'rules_entity_presave:node']],
      'expression' => $rule2->getConfiguration(),
    ]);
    $config_entity->save();

    $assert = $this->assertSession();

    // First, create a new scheduler-enabled node, triggering rule 1.
    $edit = [
      'title[0][value]' => 'New node - Trigger Action Rule 1',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('New node - Trigger Action Rule 1');
    $this->assertSession()->pageTextContains(sprintf('%s is scheduled to be published %s', 'New node - Trigger Action Rule 1', $publish_on_formatted));

    // Check that rule 1 is triggered and rule 2 is not. Check that a publishing
    // date has been set and the status is now unpublished.
    $assert->pageTextContains($message1);
    $assert->pageTextNotContains($message2);
    $this->assertEquals($node->publish_on->value, $publish_on, 'Node is scheduled for publishing at the correct time.');
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    $this->assertFalse($node->isPublished(), 'Node is now unpublished for title: "' . $node->title->value . '".');

    // Second, edit a pre-existing Scheduler-enabled node, without triggering
    // either of the rules.
    $node = $this->node_a;
    $edit = [
      'title[0][value]' => 'Edit node - but no rules will be triggered',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check that neither of the rules are triggered, no publish and unpublish
    // dates are set and the status is still published.
    $assert->pageTextNotContains($message1);
    $assert->pageTextNotContains($message2);
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    $this->assertTrue($node->isPublished(), 'Node remains published for title: "' . $node->title->value . '".');

    // Edit the node, triggering rule 1.
    $edit = [
      'title[0][value]' => 'Edit node - Trigger Action Rule 1',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check that rule 1 is triggered and rule 2 is not. Check that a publishing
    // date has been set and the status is now unpublished.
    $assert->pageTextContains($message1);
    $assert->pageTextNotContains($message2);
    $this->assertNotEmpty($node->publish_on->value, 'Node is scheduled for publishing.');
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    $this->assertFalse($node->isPublished(), 'Node is now unpublished for title: "' . $node->title->value . '".');

    // Edit the node, triggering rule 2.
    $edit = [
      'title[0][value]' => 'Edit node - Trigger Action Rule 2',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check that rule 2 is triggered and rule 1 is not. Check that the
    // publishing date has been removed and the status is now published.
    $assert->pageTextNotContains($message1);
    $assert->pageTextContains($message2);
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    $this->assertTrue($node->isPublished(), 'Node is now published for title: "' . $node->title->value . '".');

    // Third, create a new node which is not scheduler-enabled.
    $edit = [
      'title[0][value]' => 'New non-enabled node - Trigger Action Rule 1',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->nonSchedulerNodeType->id());
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('New non-enabled node - Trigger Action Rule 1');
    // Check that rule 1 issued a warning message.
    $assert->pageTextContains('warning message');
    $assert->elementExists('xpath', '//div[@aria-label="Warning message" and contains(string(), "Action")]');
    // Check that no publishing date is set.
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    // Check that a log message has been recorded.
    $log = \Drupal::database()->select('watchdog', 'w')
      ->condition('type', 'scheduler')
      ->condition('severity', RfcLogLevel::WARNING)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(1, $log, 'There is 1 watchdog warning message from Scheduler');

    // Fourthly, edit a pre-existing node which is not enabled for Scheduler.
    $node = $this->node_b;

    // Edit the node, triggering rule 1.
    $edit = [
      'title[0][value]' => 'Edit non-enabled node - Trigger Action Rule 1',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Check that rule 1 issued a warning message.
    $assert->pageTextContains('warning message');
    $assert->elementExists('xpath', '//div[@aria-label="Warning message" and contains(string(), "Action")]');
    // Check that no publishing date is set.
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    // Check that a log message has been recorded.
    $log = \Drupal::database()->select('watchdog', 'w')
      ->condition('type', 'scheduler')
      ->condition('severity', RfcLogLevel::WARNING)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(2, $log, 'There are now 2 watchdog warning messages from Scheduler');

    // Edit the node, triggering rule 2.
    $edit = [
      'title[0][value]' => 'Edit non-enabled node - Trigger Action Rule 2',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Check that rule 2 issued a warning message.
    $assert->pageTextContains('warning message');
    $assert->elementExists('xpath', '//div[@aria-label="Warning message" and contains(string(), "Action")]');
    // Check that a second log message has been recorded.
    $log = \Drupal::database()->select('watchdog', 'w')
      ->condition('type', 'scheduler')
      ->condition('severity', RfcLogLevel::WARNING)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(3, $log, 'There are now 3 watchdog warning messages from Scheduler');
  }

  /**
   * Tests the actions which set and remove the 'Unpublish On' date.
   */
  public function testUnpublishOnActions() {

    $unpublish_on = $this->requestTime + 2400;
    $unpublish_on_formatted = $this->dateFormatter->format($unpublish_on, 'long');

    // Create rule 3 to set the unpublishing date.
    $rule3 = $this->expressionManager->createRule();
    $rule3->addCondition('rules_data_comparison',
        ContextConfig::create()
          ->map('data', 'node.title.value')
          ->setValue('operation', 'contains')
          ->setValue('value', 'Trigger Action Rule 3')
    );
    $message3 = 'RULES message 3. Action to set Unpublish-on date.';
    $rule3->addAction('scheduler_set_unpublishing_date_action',
      ContextConfig::create()
        ->map('node', 'node')
        ->setValue('date', $unpublish_on)
      )
      ->addAction('rules_system_message',
        ContextConfig::create()
          ->setValue('message', $message3)
          ->setValue('type', 'status')
    );
    // The event needs to be rules_entity_presave:node 'before saving' because
    // rules_entity_update:node 'after save' is too late to set the date.
    $config_entity = $this->rulesStorage->create([
      'id' => 'rule3',
      'events' => [['event_name' => 'rules_entity_presave:node']],
      'expression' => $rule3->getConfiguration(),
    ]);
    $config_entity->save();

    // Create rule 4 to remove the unpublishing date and unpublish the node.
    $rule4 = $this->expressionManager->createRule();
    $rule4->addCondition('rules_data_comparison',
        ContextConfig::create()
          ->map('data', 'node.title.value')
          ->setValue('operation', 'contains')
          ->setValue('value', 'Trigger Action Rule 4')
    );
    $message4 = 'RULES message 4. Action to remove Unpublish-on date and unpublish the node immediately.';
    $rule4->addAction('scheduler_remove_unpublishing_date_action',
      ContextConfig::create()
        ->map('node', 'node')
      )
      ->addAction('scheduler_unpublish_now_action',
        ContextConfig::create()
          ->map('node', 'node')
      )
      ->addAction('rules_system_message',
        ContextConfig::create()
          ->setValue('message', $message4)
          ->setValue('type', 'status')
      );
    $config_entity = $this->rulesStorage->create([
      'id' => 'rule4',
      'events' => [['event_name' => 'rules_entity_presave:node']],
      'expression' => $rule4->getConfiguration(),
    ]);
    $config_entity->save();

    $assert = $this->assertSession();

    // First, create a new scheduler-enabled node, triggering rule 3.
    $edit = [
      'title[0][value]' => 'New node - Trigger Action Rule 3',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('New node - Trigger Action Rule 3');
    $this->assertSession()->pageTextContains(sprintf('%s is scheduled to be unpublished %s', 'New node - Trigger Action Rule 3', $unpublish_on_formatted));

    // Check that rule 3 is triggered and rule 4 is not. Check that a publishing
    // date has been set and the status is now unpublished.
    $assert->pageTextContains($message3);
    $assert->pageTextNotContains($message4);
    $this->assertEquals($node->unpublish_on->value, $unpublish_on, 'Node is scheduled for unpublishing at the correct time.');
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    $this->assertTrue($node->isPublished(), 'Node is published for title: "' . $node->title->value . '".');

    // Second, edit a pre-existing Scheduler-enabled node, without triggering
    // either of the rules.
    $node = $this->node_a;
    $edit = [
      'title[0][value]' => 'Edit node - but no rules will be triggered',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check that neither of the rules are triggered, no publish and unpublish
    // dates are set and the status is still published.
    $assert->pageTextNotContains($message3);
    $assert->pageTextNotContains($message4);
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    $this->assertTrue($node->isPublished(), 'Node remains published for title: "' . $node->title->value . '".');

    // Edit the node, triggering rule 3.
    $edit = [
      'title[0][value]' => 'Edit node - Trigger Action Rule 3',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check that rule 3 is triggered and rule 4 is not. Check that an
    // unpublishing date has been set and the status is still published.
    $assert->pageTextContains($message3);
    $assert->pageTextNotContains($message4);
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    $this->assertNotEmpty($node->unpublish_on->value, 'Node is scheduled for unpublishing.');
    $this->assertTrue($node->isPublished(), 'Node is still published for title: "' . $node->title->value . '".');

    // Edit the node, triggering rule 4.
    $edit = [
      'title[0][value]' => 'Edit node - Trigger Action Rule 4',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    // Check that rule 4 is triggered and rule 3 is not. Check that the
    // unpublishing date has been removed and the status is now unpublished.
    $assert->pageTextNotContains($message3);
    $assert->pageTextContains($message4);
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    $this->assertFalse($node->isPublished(), 'Node is now unpublished for title: "' . $node->title->value . '".');

    // Third, create a new node which is not scheduler-enabled.
    $edit = [
      'title[0][value]' => 'New non-enabled node - Trigger Action Rule 3',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/add/' . $this->nonSchedulerNodeType->id());
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('New non-enabled node - Trigger Action Rule 3');
    // Check that rule 3 issued a warning message.
    $assert->pageTextContains('warning message');
    $assert->elementExists('xpath', '//div[@aria-label="Warning message" and contains(string(), "Action")]');
    // Check that no publishing date is set.
    $this->assertEmpty($node->publish_on->value, 'Node is not scheduled for publishing.');
    // Check that a log message has been recorded.
    $log = \Drupal::database()->select('watchdog', 'w')
      ->condition('type', 'scheduler')
      ->condition('severity', RfcLogLevel::WARNING)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(1, $log, 'There is 1 watchdog warning message from Scheduler');

    // Fourthly, edit a pre-existing node which is not enabled for Scheduler.
    $node = $this->node_b;

    // Edit the node, triggering rule 3.
    $edit = [
      'title[0][value]' => 'Edit non-enabled node - Trigger Action Rule 3',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Check that rule 3 issued a warning message.
    $assert->pageTextContains('warning message');
    $assert->elementExists('xpath', '//div[@aria-label="Warning message" and contains(string(), "Action")]');
    // Check that no unpublishing date is set.
    $this->assertEmpty($node->unpublish_on->value, 'Node is not scheduled for unpublishing.');
    // Check that a log message has been recorded.
    $log = \Drupal::database()->select('watchdog', 'w')
      ->condition('type', 'scheduler')
      ->condition('severity', RfcLogLevel::WARNING)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(2, $log, 'There are now 2 watchdog warning messages from Scheduler');

    // Edit the node, triggering rule 4.
    $edit = [
      'title[0][value]' => 'Edit non-enabled node - Trigger Action Rule 4',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Check that rule 4 issued a warning message.
    $assert->pageTextContains('warning message');
    $assert->elementExists('xpath', '//div[@aria-label="Warning message" and contains(string(), "Action")]');
    // Check that a second log message has been recorded.
    $log = \Drupal::database()->select('watchdog', 'w')
      ->condition('type', 'scheduler')
      ->condition('severity', RfcLogLevel::WARNING)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(3, $log, 'There are now 3 watchdog warning messages from Scheduler');
  }

}
