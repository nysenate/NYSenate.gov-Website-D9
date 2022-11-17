<?php

namespace Drupal\Tests\moderation_sidebar\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
if (!trait_exists('\Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait')) {
  class_alias('\Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait', '\Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait');
}

/**
 * Contains Moderation Sidebar integration tests.
 *
 * @group moderation_sidebar
 */
class ModerationSidebarTaxonomyTermTest extends WebDriverTestBase {

  use TaxonomyTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
    'moderation_sidebar',
    'taxonomy_test',
    'moderation_sidebar_test',
    // 'content_translation',
    // 'path',
  ];

  /**
   * The vocabulary to be used for the tests.
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

    // Create a Content Type with moderation enabled.
    $this->vocabulary = $this->createVocabulary();

    // Create a user who can use the Moderation Sidebar.
    $user = $this->drupalCreateUser([
      'access toolbar',
      'use moderation sidebar',
      'administer themes',
      'access content',
      'create terms in ' . $this->vocabulary->id(),
    ]);
    $this->drupalLogin($user);

    // Enable admin theme for content forms.
    $edit = ['use_admin_theme' => TRUE];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');

    drupal_flush_all_caches();
  }

  /**
   * Tests that the Moderation Sidebar is working as expected.
   */
  public function testModerationSidebar() {
    // Create a new term.
    $term = $this->createTerm($this->vocabulary);
    $this->drupalGet($term->toUrl()->toString());

    // Open the moderation sidebar.
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', '.moderation-sidebar-info');
    $this->assertSession()->elementTextContains('css', '.moderation-sidebar-info', 'Published');

    $this->assertSession()->elementTextContains('css', 'a.moderation-sidebar-link', 'View');
    $this->assertSession()->elementAttributeContains('css', 'a.moderation-sidebar-link', 'href', $term->toUrl()->toString());
    // We don't have permission to edit.
    $this->assertSession()->elementNotExists('css', 'a.moderation-sidebar-link + a.moderation-sidebar-link');

    // Login as user able to edit the term.
    $user = $this->drupalCreateUser([
      'access toolbar',
      'use moderation sidebar',
      'access content',
      'edit terms in ' . $this->vocabulary->id(),
    ]);
    $this->drupalLogin($user);
    $this->drupalGet($term->toUrl()->toString());
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementTextContains('css', 'a.moderation-sidebar-link', 'View');
    $this->assertSession()->elementExists('css', 'a.moderation-sidebar-link + a.moderation-sidebar-link');
    $this->assertSession()->elementTextContains('css', 'a.moderation-sidebar-link + a.moderation-sidebar-link', 'Edit');
    $this->assertSession()->elementAttributeContains('css', 'a.moderation-sidebar-link + a.moderation-sidebar-link', 'href', $term->toUrl()->toString() . '/edit');
  }

}
