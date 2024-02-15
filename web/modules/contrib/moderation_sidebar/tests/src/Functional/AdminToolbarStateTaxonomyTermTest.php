<?php

namespace Drupal\Tests\moderation_sidebar\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
if (!trait_exists('\Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait')) {
  class_alias('\Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait', '\Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait');
}

/**
 * Contains test for the toolbar state label for taxonomy_term.
 *
 * @group moderation_sidebar
 */
class AdminToolbarStateTaxonomyTermTest extends BrowserTestBase {

  use ContentModerationTestTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'moderation_sidebar', 'taxonomy'];

  /**
   * Vocabulary to be used for tests.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->vocabulary = $this->createVocabulary();

    $user = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($user);
  }

  /**
   * Tests state labels in admin toolbar with a moderated entity.
   */
  public function testModeratedEntity() {
    // This is empty because taxonomy_term can't be used in a moderation
    // workflow yet. Test must be provided when #2899923 is merged into core.
    // See https://www.drupal.org/project/drupal/issues/2899923.
  }

  /**
   * Tests state labels in admin toolbar with a not moderated entity.
   */
  public function testNotModeratedEntity() {
    $term = $this->createTerm($this->vocabulary);
    $url = $term->toUrl()->toString();
    $assert_session = $this->assertSession();

    // Draft.
    $term->set('status', 0);
    $term->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');

    // Published.
    $term->set('status', 1);
    $term->save();
    $this->drupalGet($url);
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
  }

}
