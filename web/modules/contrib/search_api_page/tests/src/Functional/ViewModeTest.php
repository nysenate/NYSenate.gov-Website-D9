<?php

namespace Drupal\Tests\search_api_page\Functional;

/**
 * Provides web tests for Search API Pages.
 *
 * @group search_api_page
 */
class ViewModeTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create blog content type and create one node of this type.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->drupalCreateNode([
      'title' => 'Title blog number 1',
      'type' => 'blog',
      'body' => [['value' => 'This is the body text for blog number 1.']],
    ]);

    // Create document content type and create one node of this type.
    $this->drupalCreateContentType(['type' => 'document']);
    $this->drupalCreateNode([
      'title' => 'Title document number 1',
      'type' => 'document',
      'body' => [['value' => 'This is the body text for document number 1.']],
    ]);
  }

  /**
   * Tests whether the default view mode and the overrides can be
   * used for a Search API Page.
   */
  public function testDefaultViewModeAndOverrides() {
    $this->drupalLogin($this->adminUser);
    $assert_session = $this->assertSession();

    // Setup Search API server and index.
    $this->setupSearchApi();

    // The body field of a Document is not visible in the teaser view mode.
    $this->drupalGet('admin/structure/types/manage/document/display/teaser');
    $assert_session->statusCodeEquals(200);
    $edit = [
      'fields[body][region]' => 'hidden',
    ];
    $this->submitForm($edit, 'Save');

    // Create a Search API Page and verify that it exists.
    $step1 = [
      'label' => 'Search Page Test',
      'id' => 'search_page_test',
      'index' => $this->index->id(),
    ];
    $this->drupalGet('admin/config/search/search-api-pages/add');
    $this->submitForm($step1, 'Next');
    $step2 = [
      'path' => 'search-page-test',
    ];
    $this->submitForm($step2, 'Save');
    $this->drupalGet('admin/config/search/search-api-pages');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Search Page Test');

    // The default view mode is chosen as default.
    // For the content type Document, the teaser view mode is chosen.
    $this->drupalGet('admin/config/search/search-api-pages/search_page_test');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Default View mode all Content bundles');
    $assert_session->pageTextContains('Content View mode overrides');
    $edit = [
      'view_mode_configuration[entity:node][default]' => 'default',
      'view_mode_configuration[entity:node][overrides][document]' => 'teaser',
    ];
    $this->submitForm($edit, 'Save');

    // Perform a few searches and check whether the outcome is as expected.
    $this->drupalGet('search-page-test');
    $assert_session->statusCodeEquals(200);
    $this->submitForm(['keys' => 'blog number 1'], 'Search');
    $assert_session->pageTextContains('Title blog number 1');
    $assert_session->pageTextContains('This is the body text for blog number 1.');
    $this->submitForm(['keys' => 'document number 1'], 'Search');
    $assert_session->pageTextContains('Title document number 1');
    $assert_session->pageTextNotContains('This is the body text for document number 1.');
  }

}
