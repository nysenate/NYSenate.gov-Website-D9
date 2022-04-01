<?php

namespace Drupal\Tests\search_api_page\Functional;

use Drupal\search_api\Item\Field;
use Drupal\search_api_page\Entity\SearchApiPage;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\TestHelperTrait;

/**
 * Provides web tests for Search API Pages's integration with facets.
 *
 * @group search_api_page
 */
class FacetsIntegrationTest extends FunctionalTestBase {

  use TestHelperTrait;
  use BlockTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer search_api',
      'administer search_api_page',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
      'view search api pages',
      'administer facets',
    ]);

    $this->drupalLogin($this->adminUser);
    $this->setupSearchApi();

    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 1; $i < 12; $i++) {
      $this->drupalCreateNode([
        'title' => 'Page number' . $i,
        'type' => 'page',
        'body' => [['value' => "Page $i body."]],
      ]);
    }

    // Index the taxonomy and entity reference fields.
    $type_field = new Field($this->index, 'type');
    $type_field->setType('string');
    $type_field->setPropertyPath('type');
    $type_field->setDatasourceId('entity:node');
    $type_field->setLabel('Type');

    $this->index->addField($type_field);
    $this->index->save();
    $this->indexItems($this->index->id());
  }

  /**
   * Test search api pages.
   */
  public function testFacets() {
    $page = SearchApiPage::create([
      'label' => 'Owl Display',
      'id' => 'owl_display',
      'index' => $this->index->id(),
      'path' => 'bird_owl',
      'show_all_when_no_keys' => TRUE,
    ]);
    $page->save();
    $this->createFacet('Eurasian Eagle-Owl', 'eagle_owl', 'type', 'owl_display', 'search_api_page');

    // Clear the caches because creating a search page is not picked up by the
    // routing otherwise.
    // @todo: Fix that.
    $this->resetAll();

    $this->drupalGet('bird_owl');
    $this->assertFacetBlocksAppear();
    $this->assertFacetLabel('page');
    $this->assertFacetLabel('article');
    $this->assertSession()->pageTextContains('60 results found');

    $this->clickPartialLink('page');
    $this->assertSession()->pageTextContains('11 results found');
  }

}
