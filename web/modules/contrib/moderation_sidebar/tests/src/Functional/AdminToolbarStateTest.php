<?php

namespace Drupal\Tests\moderation_sidebar\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Contains test for the toolbar state label.
 *
 * @group moderation_sidebar
 */
class AdminToolbarStateTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'moderation_sidebar', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $node_type = $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $node_type->setNewRevision(TRUE);
    $node_type->save();

    $user = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($user);
  }

  /**
   * Tests state labels in admin toolbar with a moderated entity.
   */
  public function testModeratedEntity() {
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    $node = $this->createNode(['type' => 'article']);
    $url = $node->toUrl()->toString();
    $assert_session = $this->assertSession();

    // Draft.
    $node->set('moderation_state', 'draft');
    $node->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');

    // Published.
    $node->set('moderation_state', 'published');
    $node->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');

    // Archived.
    $node->set('moderation_state', 'archived');
    $node->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Archived"]');
  }

  /**
   * Tests state labels in admin toolbar with a not moderated entity.
   */
  public function testNotModeratedEntity() {
    $node = $this->createNode(['type' => 'article']);
    $url = $node->toUrl()->toString();
    $assert_session = $this->assertSession();

    // Draft.
    $node->set('status', NodeInterface::NOT_PUBLISHED);
    $node->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');

    // Published.
    $node->set('status', NodeInterface::PUBLISHED);
    $node->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
  }

}
