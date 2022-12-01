<?php

namespace Drupal\Tests\search_api_page\Functional;

/**
 * Provides web tests for the parse mode option of Search API Pages.
 *
 * @group search_api_page
 */
class ParseModeTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
    $this->setupSearchApi();
  }

  /**
   * Test the parse mode configuration in the admin form of a Search API Page.
   */
  public function testAdminFormParseMode() {
    $assert_session = $this->assertSession();

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

    // Test whether all parse mode plugins can be chosen.
    $this->drupalGet('admin/config/search/search-api-pages/search_page_test');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Parse mode for search keywords');

    $plugin_manager = \Drupal::service('plugin.manager.search_api.parse_mode');
    $instances = $plugin_manager->getInstances();
    foreach ($instances as $name => $instance) {
      $assert_session->responseContains($name);
    }

    // Test whether the field can be filled and submitted.
    $edit = [
      'parse_mode' => 'terms',
    ];
    $this->drupalGet('admin/config/search/search-api-pages/search_page_test');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/config/search/search-api-pages/search_page_test');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('terms');
  }

}
