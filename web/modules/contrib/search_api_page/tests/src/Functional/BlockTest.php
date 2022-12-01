<?php

namespace Drupal\Tests\search_api_page\Functional;

/**
 * Provides web tests for Search API Pages.
 *
 * @group search_api_page
 */
class BlockTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    // Setup search api server and index.
    $this->setupSearchApi();
    $this->createSearchPage('search', 'Search', 'search', $this->index->id());
    $this->createSearchPage('other_search', 'Search (Other)', 'other_search', $this->index->id());
    $this->placeSearchBlock('search', 1);
    $this->placeSearchBlock('other_search', 2);

    $this->drupalGet('<front>');
  }

  /**
   * Creates a search page.
   *
   * @param string $id
   *   The page id.
   * @param string $label
   *   The page label.
   * @param string $path
   *   The page path.
   * @param int $indexId
   *   The id of index to use.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function createSearchPage($id, $label, $path, $indexId) {
    $this->drupalGet('admin/config/search/search-api-pages');
    $this->assertSession()->statusCodeEquals(200);

    $step1 = [
      'label' => $label,
      'id' => $id,
      'index' => $indexId,
    ];
    $this->drupalGet('admin/config/search/search-api-pages/add');
    $this->submitForm($step1, 'Next');

    $step2 = [
      'path' => $path,
    ];
    $this->submitForm($step2, 'Save');
  }

  /**
   * Places a search block for a given page.
   *
   * @param string $pageId
   *   The page id.
   * @param int $weight
   *   The weight.
   */
  private function placeSearchBlock($pageId, $weight) {
    $this->drupalPlaceBlock('search_api_page_form_block', ['search_api_page' => $pageId, 'weight' => $weight]);
  }

  /**
   * Tests that the search form block works.
   */
  public function testSearchForm() {
    $form = $this->getSession()->getPage()->find('css', '.search-form form');
    $form->fillField('Search', 'Owls');
    $form->submit();

    $this->assertSession()->statusCodeEquals(200);
    $this->assertPath('search/Owls');
    $this->assertSession()->pageTextContains('9 results found');
  }

  /**
   * Asserts that we are on the expected path.
   *
   * @param string $expectedPath
   *   The expected path.
   */
  private function assertPath($expectedPath) {
    $url = $this->buildUrl($expectedPath, ['absolute' => TRUE]);
    $this->assertEquals($url, $this->getSession()->getCurrentUrl());
  }

  /**
   * Tests search forms search on the correct page when multiple configured.
   */
  public function testSearchFormsSearchOnCorrectPage() {
    $forms = $this->getSession()->getPage()->findAll('css', '.search-form form');
    $form = end($forms);
    $form->fillField('Search', 'Owls');
    $form->submit();

    $this->assertSession()->statusCodeEquals(200);
    $this->assertPath('other_search/Owls');
    $this->assertSession()->pageTextContains('9 results found');
  }

  /**
   * Tests that only the initiating search block has the keys as default value.
   */
  public function testDefaultValue() {
    $form = $this->getSession()->getPage()->find('css', '.search-form form');
    $form->fillField('Search', 'Owls');
    $form->submit();

    $this->assertSession()->fieldValueEquals("edit-keys", 'Owls');
    $this->assertSession()->fieldValueEquals('edit-keys--2', '');
  }

}
