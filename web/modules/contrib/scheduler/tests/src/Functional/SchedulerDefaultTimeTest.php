<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the default time functionality.
 *
 * @group scheduler
 */
class SchedulerDefaultTimeTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  protected static $modules = ['scheduler_extras'];

  /**
   * The default time.
   *
   * @var string
   */
  protected $defaultTime;

  /**
   * The Publish On datetime derived using the default time.
   *
   * @var \DateTime
   */
  protected $publishTime;

  /**
   * The Unpublish On datetime derived using the default time.
   *
   * @var \DateTime
   */
  protected $unpublishTime;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // For this test we use a default time of 6:30:15am.
    $this->defaultTime = '06:30:15';
    $config = $this->config('scheduler.settings');
    $config->set('default_time', $this->defaultTime)->save();

    // Create DateTime objects to hold the two scheduling dates. This is better
    // than using raw unix timestamps because it caters for daylight-saving
    // shifts properly.
    // @see https://www.drupal.org/project/scheduler/issues/2957490
    $this->publishTime = new \DateTime();
    $this->publishTime->add(new \DateInterval('P1D'))->setTime(6, 30, 15);

    $this->unpublishTime = new \DateTime();
    $this->unpublishTime->add(new \DateInterval('P2D'))->setTime(6, 30, 15);
  }

  /**
   * Test the default time functionality during content creation and edit.
   *
   * This test covers the default scenario where the dates are optional and not
   * required. A javascript test covers the cases where the dates are required.
   */
  public function testDefaultTime() {
    $this->drupalLogin($this->schedulerUser);
    $config = $this->config('scheduler.settings');

    // We cannot easily test the full validation message as they contain the
    // current time which can be one or two seconds in the past. The best we can
    // do is check the fixed part of the message as it is when passed to t() in
    // Datetime::validateDatetime. Tests only needs to work in English anyway.
    $publish_validation_message = 'The Publish on date is invalid.';
    $unpublish_validation_message = 'The Unpublish on date is invalid.';

    // First test with the "date only" functionality disabled.
    $config->set('allow_date_only', FALSE)->save();

    // Test that entering a time is required.
    $edit = [
      'title[0][value]' => 'No time ' . $this->randomMachineName(8),
      'publish_on[0][value][date]' => $this->publishTime->format('Y-m-d'),
      'unpublish_on[0][value][date]' => $this->unpublishTime->format('Y-m-d'),
    ];
    // Create a node and check that the expected error messages are shown.
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    // By default it is required to enter a time when scheduling content for
    // publishing and for unpublishing.
    $this->assertSession()->pageTextNotMatches('/' . $edit['title[0][value]'] . ' is scheduled to be published .* and unpublished .*/');
    $this->assertSession()->pageTextContains($publish_validation_message);
    $this->assertSession()->pageTextContains($unpublish_validation_message);

    // Allow the user to enter only a date with no time.
    $config->set('allow_date_only', TRUE)->save();

    // Create a node and check that the validation messages are not shown.
    $this->drupalGet('node/add/' . $this->type);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextNotContains($publish_validation_message);
    $this->assertSession()->pageTextNotContains($unpublish_validation_message);

    // Get the pattern of the 'long' default date format.
    $date_format_storage = $this->container->get('entity_type.manager')->getStorage('date_format');
    $long_pattern = $date_format_storage->load('long')->getPattern();

    // Check that the scheduled information is shown after saving.
    $this->assertSession()->pageTextContains(sprintf('%s is scheduled to be published %s and unpublished %s',
      $edit['title[0][value]'], $this->publishTime->format($long_pattern), $this->unpublishTime->format($long_pattern)));

    // Protect this section in case the node was not created.
    if ($node = $this->drupalGetNodeByTitle($edit['title[0][value]'])) {
      // Check that the correct scheduled dates are stored in the node.
      $this->assertEquals($this->publishTime->getTimestamp(), (int) $node->publish_on->value, 'The node publish_on value is stored correctly.');
      $this->assertEquals($this->unpublishTime->getTimestamp(), (int) $node->unpublish_on->value, 'The node unpublish_on value is stored correctly.');

      // Check that the default time has been added to the form on edit.
      $this->drupalGet('node/' . $node->id() . '/edit');
      $this->assertSession()->FieldValueEquals('publish_on[0][value][time]', $this->defaultTime);
      $this->assertSession()->FieldValueEquals('unpublish_on[0][value][time]', $this->defaultTime);
    }
    else {
      $this->fail('The expected node was not found.');
    }
  }

  /**
   * Test that the default times are set if the form time elements are hidden.
   */
  public function testDefaultTimeWithHiddenTime() {
    // Create a content type that will have both of the time form elements
    // hidden. See hook_form_node_form_alter() in scheduler_extras test module.
    $type = 'hidden_time';
    $typeName = 'Content with hidden times';
    /** @var NodeTypeInterface $nodetype */
    $nodetype = $this->drupalCreateContentType([
      'type' => $type,
      'name' => $typeName,
    ]);

    // Add scheduler functionality to the content type.
    $nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Log in as a user with permission to create and schedule this type.
    $this->drupalLogin($this->drupalCreateUser([
      'create ' . $type . ' content',
      'edit own ' . $type . ' content',
      'delete own ' . $type . ' content',
      'view own unpublished content',
      'schedule publishing of nodes',
      'view scheduled content',
    ]));

    // Allow the user to enter only a date with no time.
    $this->config('scheduler.settings')->set('allow_date_only', TRUE)->save();

    // Test that entering a time is required.
    $edit = [
      'title[0][value]' => 'Hidden Time Elements ' . $this->randomMachineName(8),
      'publish_on[0][value][date]' => $this->publishTime->format('Y-m-d'),
      'unpublish_on[0][value][date]' => $this->unpublishTime->format('Y-m-d'),
    ];
    // Create a node and check that the expected default time has been saved.
    $this->drupalGet('node/add/' . $type);
    $this->submitForm($edit, 'Save');

    // Get the pattern of the 'long' default date format.
    $date_format_storage = $this->container->get('entity_type.manager')->getStorage('date_format');
    $long_pattern = $date_format_storage->load('long')->getPattern();

    // Check that the message has the correct default time after saving.
    $this->assertSession()->pageTextContains(sprintf('%s is scheduled to be published %s and unpublished %s',
      $edit['title[0][value]'], $this->publishTime->format($long_pattern), $this->unpublishTime->format($long_pattern)));

    // Protect this section in case the node was not created.
    if ($node = $this->drupalGetNodeByTitle($edit['title[0][value]'])) {
      // Check that the correct scheduled dates are stored in the node.
      $this->assertEquals($this->publishTime->getTimestamp(), (int) $node->publish_on->value, 'The node publish_on value is stored correctly.');
      $this->assertEquals($this->unpublishTime->getTimestamp(), (int) $node->unpublish_on->value, 'The node unpublish_on value is stored correctly.');
    }
    else {
      $this->fail('The expected node was not found.');
    }
  }

}
