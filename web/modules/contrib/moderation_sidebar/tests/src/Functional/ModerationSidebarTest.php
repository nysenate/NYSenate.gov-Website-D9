<?php

namespace Drupal\Tests\moderation_sidebar\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests basic behaviour of Moderation Sidebar using a test entity.
 *
 * @group moderation_sidebar
 */
class ModerationSidebarTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'moderation_sidebar',
    'toolbar',
    'content_moderation',
    'node',
    'workflows',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'entity_test_mulrevpub', 'entity_test_mulrevpub');

    $this->drupalCreateContentType(['type' => 'article']);

    $this->drupalLogin($this->createUser([
      'view test entity',
      'access toolbar',
      'create article content',
      'use ' . $workflow->id() . ' transition create_new_draft',
      'use ' . $workflow->id() . ' transition archive',
      'use ' . $workflow->id() . ' transition publish',
      'use moderation sidebar',
    ]));
  }

  /**
   * Test toolbar item appears.
   */
  public function testToolbarItem() {
    $entity = EntityTestMulRevPub::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $this->drupalGet($entity->toUrl());

    // Make sure the button is where we expect it.
    $toolbarItem = $this->assertSession()->elementExists('css', '.moderation-sidebar-toolbar-tab a');
    // Make sure the button has the right attributes.
    $url = Url::fromRoute('moderation_sidebar.sidebar_latest', [
      'entity_type' => $entity->getEntityTypeId(),
      'entity' => $entity->id(),
    ]);
    $this->assertEquals($url->toString(), $toolbarItem->getAttribute('href'));
    $this->assertEquals('Tasks', $toolbarItem->getText());
  }

  /**
   * Test preview with moderation sidebar.
   */
  public function testPreview() {
    $title_key = 'title[0][value]';

    // Create an english node with an english menu.
    $this->drupalGet('/node/add/article');
    $edit = [
      $title_key => $this->randomMachineName(),
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Preview');

    // Check that the preview is displaying the title, body and term.
    $expected_title = $edit[$title_key] . ' | Drupal';
    $this->assertSession()->titleEquals($expected_title);
    $this->assertSession()->linkExists('Back to content editing');
  }

}
