<?php

namespace Drupal\Tests\search_api_page\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api_page\Entity\SearchApiPage;

/**
 * Provides web tests for Search API Pages with language integration.
 *
 * @group search_api_page
 */
class LanguageIntegrationTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
    $assert_session = $this->assertSession();

    ConfigurableLanguage::create([
      'id' => 'nl',
      'label' => 'Dutch',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'es',
      'label' => 'Spanish',
    ])->save();

    $bird_node = $this->drupalCreateNode([
      'title' => 'bird: Hawk',
      'language' => 'en',
      'type' => 'article',
      'body' => [['value' => 'Body translated']],
    ]);
    $bird_node->addTranslation('nl', ['title' => 'bird: Havik'])
      ->addTranslation('es', ['title' => 'bird: Halcon'])
      ->save();

    // Setup search api server and index.
    $this->setupSearchAPI();

    $this->drupalGet('admin/config/search/search-api-pages');
    $assert_session->statusCodeEquals(200);

    $step1 = [
      'label' => 'Search',
      'id' => 'search',
      'index' => $this->index->id(),
    ];
    $this->drupalGet('admin/config/search/search-api-pages/add');
    $this->submitForm($step1, 'Next');

    $step2 = [
      'path' => 'search',
    ];
    $this->submitForm($step2, 'Save');
  }

  /**
   * Tests Search API Pages language integration.
   */
  public function testSearchApiPage() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/search');
    $this->submitForm(['keys' => 'bird'], 'Search');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('1 result found');
    $assert_session->pageTextContains('Hawk');
    $assert_session->pageTextNotContains('Your search yielded no results.');

    $this->drupalGet('/nl/search');
    $this->submitForm(['keys' => 'bird'], 'Search');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('1 result found');
    $assert_session->pageTextContains('Havik');
    $assert_session->pageTextNotContains('Your search yielded no results.');
  }

  /**
   * Tests the url alias translation.
   *
   * @see https://www.drupal.org/node/2893374
   */
  public function testUrlAliasTranslation() {
    $page = SearchApiPage::create([
      'label' => 'Owl Display',
      'id' => 'owl_display',
      'index' => $this->index->id(),
      'path' => 'bird_owl',
      'show_all_when_no_keys' => TRUE,
    ]);
    $page->save();

    \Drupal::service('module_installer')->install(['locale']);
    $block = $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, [
      'id' => 'test_language_block',
    ]);

    $this->drupalGet('bird_owl');
    $this->assertSession()->pageTextContains($block->label());
    $this->assertSession()->pageTextContains('50 results found');
    $this->assertSession()->statusCodeEquals(200);

    $this->clickLink('Spanish');
    $this->assertSession()->pageTextContains($block->label());
    $this->assertTrue((bool) strpos($this->getUrl(), '/es/'), 'Found the language code in the url');
    $this->assertSession()->pageTextContains('1 result found');
    $this->assertSession()->statusCodeEquals(200);

    $this->clickLink('Dutch');
    $this->assertSession()->pageTextContains($block->label());
    $this->assertTrue((bool) strpos($this->getUrl(), '/nl/'), 'Found the language code in the url');
    $this->assertSession()->pageTextContains('1 result found');
    $this->assertSession()->statusCodeEquals(200);

    $this->clickLink('English');
    $this->assertSession()->pageTextContains($block->label());
    $this->assertSession()->pageTextContains('50 results found');
    $this->assertSession()->statusCodeEquals(200);

    // Test that keys are properly preserved when switching languages.
    $this->drupalGet('/search');
    $this->submitForm(['keys' => 'bird'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('1 result found');
    $this->assertSession()->pageTextContains('Hawk');
    $this->clickLink('Spanish');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('1 result found');
    $this->assertSession()->pageTextContains('Halcon');
    $this->clickLink('Dutch');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('1 result found');
    $this->assertSession()->pageTextContains('Havik');
    $this->clickLink('English');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('1 result found');
    $this->assertSession()->pageTextContains('Hawk');
  }

}
