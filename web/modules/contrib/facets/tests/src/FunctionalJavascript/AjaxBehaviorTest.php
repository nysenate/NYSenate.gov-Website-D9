<?php

namespace Drupal\Tests\facets\FunctionalJavascript;

use Drupal\views\Entity\View;

/**
 * Tests for the JS that powers ajax.
 *
 * @group facets
 */
class AjaxBehaviorTest extends JsBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Force ajax.
    $view = View::load('search_api_test_view');
    $display = $view->getDisplay('page_1');
    $display['display_options']['use_ajax'] = TRUE;
    $view->save();
  }

  /**
   * Tests ajax links.
   */
  public function testAjaxLinks() {
    // Create facets.
    $this->createFacet('owl');
    $this->createFacet('duck', 'keywords');

    // Go to the views page.
    $this->drupalGet('search-api-test-fulltext');

    // Make sure the blocks are shown on the page.
    $page = $this->getSession()->getPage();
    $block_owl = $page->findById('block-owl-block');
    $block_owl->isVisible();
    $block_duck = $page->findById('block-duck-block');
    $block_duck->isVisible();
    $this->assertSession()->pageTextContains('Displaying 5 search results');

    // Check that the article link exists (and is formatted like a facet) link.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertNotEmpty($links);

    // Click the item facet.
    $this->clickLink('item');

    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Displaying 3 search results');

    // Check that the article facet is now gone.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertEmpty($links);

    // Click the item facet again, and check that the article facet is back.
    $this->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertNotEmpty($links);

    // Check that the strawberry link disappears when filtering on items.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'strawberry']);
    $this->assertNotEmpty($links);
    $this->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'strawberry']);
    $this->assertEmpty($links);
  }

  /**
   * Tests ajax dropdown.
   */
  public function testAjaxDropdown() {
    // Create facets.
    $this->createFacet('owl');
    $this->createFacet('duck', 'category', 'dropdown', []);

    // Go to the views page.
    $this->drupalGet('search-api-test-fulltext');

    // Make sure the blocks are shown on the page.
    $page = $this->getSession()->getPage();
    $block_owl = $page->findById('block-owl-block');
    $block_owl->isVisible();
    $block_duck = $page->findById('block-duck-block');
    $block_duck->isVisible();
    $this->assertSession()->pageTextContains('Displaying 5 search results');

    // Check that the article_category option disappears when filtering on item.
    $dropdown_entry = $this->xpath('//*[@id="block-duck-block"]/div/select/option[normalize-space(text())=:label]', [':label' => 'article_category']);
    $this->assertNotEmpty($dropdown_entry);
    $block_owl->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $dropdown_entry = $this->xpath('//*[@id="block-duck-block"]/div/select/option[normalize-space(text())=:label]', [':label' => 'article_category']);
    $this->assertEmpty($dropdown_entry);

    // Click the item facet again.
    $block_owl->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Select the article_category in the dropdown.
    $dropdown = $this->xpath('//*[@id="block-duck-block"]/div/select');
    $dropdown[0]->selectOption('article_category');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Displaying 2 search results');

    // Check that the article link exists (and is formatted like a facet) link.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertNotEmpty($links);
    // Check that the item link didn't exists.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'item']);
    $this->assertEmpty($links);
  }

  /**
   * Tests ajax checkbox.
   */
  public function testAjaxCheckbox() {
    // Create facets.
    $this->createFacet('owl');
    $this->createFacet('duck', 'keywords', 'checkbox');

    // Go to the views page.
    $this->drupalGet('search-api-test-fulltext');

    // Make sure the blocks are shown on the page.
    $page = $this->getSession()->getPage();
    $block_owl = $page->findById('block-owl-block');
    $block_owl->isVisible();
    $block_duck = $page->findById('block-duck-block');
    $block_duck->isVisible();
    $this->assertSession()->pageTextContains('Displaying 5 search results');

    // Check that the article link exists (and is formatted like a facet) link.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertNotEmpty($links);

    // Click the item facet.
    $this->clickLink('item');

    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Displaying 3 search results');

    // Check that the article facet is now gone.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertEmpty($links);

    // Click the item facet again, and check that the article facet is back.
    $this->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Displaying 5 search results');
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertNotEmpty($links);

    // Check that the strawberry link disappears when filtering on items.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'strawberry']);
    $this->assertNotEmpty($links);
    $this->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'strawberry']);
    $this->assertEmpty($links);
    $this->clickLink('item');

    $this->getSession()->getPage()->checkField('strawberry');
    // Check that the article link exists (and is formatted like a facet) link.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'article']);
    $this->assertNotEmpty($links);
    // Check that the item link didn't exists.
    $links = $this->xpath('//a//span[normalize-space(text())=:label]', [':label' => 'item']);
    $this->assertEmpty($links);
  }

  /**
   * Tests links with exposed filters.
   */
  public function testLinksWithExposedFilter() {
    $view = View::load('search_api_test_view');
    $display = $view->getDisplay('page_1');
    $display['display_options']['filters']['search_api_fulltext']['expose']['required'] = TRUE;
    $view->save();

    $this->createFacet('owl');
    $this->drupalGet('search-api-test-fulltext');

    $page = $this->getSession()->getPage();
    $block_owl = $page->findById('block-owl-block');
    $block_owl->isVisible();

    $this->assertSession()->fieldExists('edit-search-api-fulltext')->setValue('baz');
    $this->click('.form-submit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Displaying 3 search results');

    $this->clickLink('item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Displaying 1 search results');
  }

}
