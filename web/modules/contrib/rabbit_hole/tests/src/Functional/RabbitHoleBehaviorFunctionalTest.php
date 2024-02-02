<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\node\Entity\Node;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests of Rabbit Hole generic behavior.
 *
 * @group rabbit_hole
 */
class RabbitHoleBehaviorFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole', 'node'];

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $behaviorSettingsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser();
    $this->behaviorSettingsManager = $this->container->get('rabbit_hole.behavior_settings_manager');
    $this->behaviorSettingsManager->enableEntityType('node');

    BehaviorSettings::loadByEntityTypeBundle('node', 'article')
      ->setAction('page_not_found')
      ->setBypassMessage(FALSE)
      ->save();
    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('node', 'article');
  }
  /**
   * Tests the bypass warning message.
   */
  public function testBypassMessage() {
    $node1 = Node::create(['title' => '#standWithUkraine', 'type' => 'article']);
    $node1->save();

    // Verify the "Page Not Found" is working for regular users and message is
    // not displayed.
    $this->drupalGet($node1->toUrl());
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseNotContains('This page is configured to apply "Page not found" Rabbit Hole action, but you have permission to see the page.');

    // Verify the user with bypass access - the message is still disabled.
    $this->drupalLogin($this->createUser(['rabbit hole bypass node']));
    $this->drupalGet($node1->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains('This page is configured to apply "Page not found" Rabbit Hole action, but you have permission to see the page.');

    // Now enable the bypass message and check whether it's available.
    BehaviorSettings::loadByEntityTypeBundle('node', 'article')
      ->setBypassMessage(TRUE)
      ->save();
    $this->drupalGet($node1->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('This page is configured to apply "Page not found" Rabbit Hole action, but you have permission to see the page.');
  }

}
