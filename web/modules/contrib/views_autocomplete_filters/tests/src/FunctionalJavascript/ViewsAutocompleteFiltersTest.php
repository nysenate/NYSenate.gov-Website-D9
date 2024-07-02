<?php

declare(strict_types=1);

namespace Drupal\Tests\views_autocomplete_filters\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests Autocomplete filter plugins functionality.
 *
 * @group views
 */
class ViewsAutocompleteFiltersTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;

  /**
   * WebAssert object.
   *
   * @var \Drupal\FunctionalJavascriptTests\JSWebAssert
   */
  protected $webAssert;

  /**
   * DocumentElement object.
   *
   * @var \Behat\Mink\Element\DocumentElement
   */
  protected $page;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_autocomplete_filters',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Create content types and their content.
    $node_types = [
      'article',
      'page',
    ];
    foreach ($node_types as $node_type) {
      $this->createContentType(['type' => $node_type]);
      // Also create users for author name filter.
      $author = $this->createUser([], $node_type . '_author');

      for ($i = 1; $i <= 6; $i++) {
        $title = $node_type . ' ' . $i;
        $this->createNode(['type' => $node_type, 'title' => $title, 'uid' => $author->id()]);
      }
    }

    $this->page = $this->getSession()->getPage();
    $this->webAssert = $this->assertSession();
  }

  /**
   * Test Autocomplete filters.
   */
  public function testIt() {
    $view_admin_url = '/admin/structure/views/view/content';
    $view_page_url = '/admin/content/node';

    // Set the autocomplete filters.
    $this->drupalGet($view_admin_url);

    // We need to disable tab menu for setting Contextual filter.
    $this->page->clickLink('Tab: Content');
    $this->webAssert->waitForField('menu[type]');
    $this->page->selectFieldOption('menu[type]', 'none');
    $this->saveViewsUiModal();

    // Update existing title filter with autocomplete capabilities.
    $this->page->clickLink('Content: Title (exposed)');
    // "Use Autocomplete" is present.
    $this->webAssert->waitForField('options[expose][autocomplete_filter]');
    $autocomplete_field_selector = '[data-drupal-selector="edit-options-expose-autocomplete-field"]';
    $autocomplete_field_element = $this->webAssert->elementExists('css', $autocomplete_field_selector);

    // The Autocomplete options are available only after "Use Autocomplete" is
    // checked.
    $this->assertFalse($autocomplete_field_element->isVisible());
    $this->page->checkField('options[expose][autocomplete_filter]');
    $this->assertTrue($this->page->hasCheckedField('options[expose][autocomplete_filter]'));
    $this->assertTrue($autocomplete_field_element->isVisible());

    // Check options and their defaults.
    $this->assertFalse($this->page->hasCheckedField('options[expose][autocomplete_contextual]'));
    $this->assertEquals(10, $this->page->findField('options[expose][autocomplete_items]')->getValue());
    $this->assertEquals(0, $this->page->findField('options[expose][autocomplete_min_chars]')->getValue());
    $this->assertFalse($this->page->hasCheckedField('options[expose][autocomplete_dependent]'));
    $this->assertTrue($this->page->hasCheckedField('options[expose][autocomplete_raw_dropdown]'));
    $this->assertTrue($this->page->hasCheckedField('options[expose][autocomplete_raw_suggestion]'));
    $this->assertFalse($this->page->hasCheckedField('options[expose][autocomplete_autosubmit]'));

    // Finish filter setting.
    $this->page->selectFieldOption('options[expose][autocomplete_field]', 'title');
    $this->saveViewsUiModal();

    // Add author name filter.
    // It uses author/user relationship.
    $this->addViewsAutocompleteFilter('name[users_field_data.name]');
    // Add contextual filter.
    $this->page->checkField('options[expose][autocomplete_contextual]');
    $this->saveViewsUiModal();

    // Add Combine fields filter.
    // It uses both title and author name fields data.
    $this->addViewsAutocompleteFilter('name[views.combine]');

    $combine_filter_selector = 'options[fields][]';
    $this->webAssert->waitForField($combine_filter_selector);
    $this->page->selectFieldOption($combine_filter_selector, 'title', TRUE);
    $this->page->selectFieldOption($combine_filter_selector, 'name', TRUE);
    $this->page->selectFieldOption('options[operator]', 'contains');
    // Add dependent.
    $this->page->checkField('options[expose][autocomplete_dependent]');
    // Enable Auto submit option.
    $this->page->checkField('options[expose][autocomplete_autosubmit]');
    $this->saveViewsUiModal();

    // Add node id contextual filter.
    $this->page->clickLink('views-add-argument');
    $this->webAssert->waitForField('override[controls][options_search]');
    $this->page->findField('name[node_field_data.nid]')->click();
    $this->page->find('css', '.ui-dialog .ui-dialog-buttonpane')
      ->pressButton('Add and configure contextual filters');

    $field_action = $this->webAssert->waitForField('options[default_action]');
    $this->assertNotEmpty($field_action);
    $field_action->setValue('ignore');
    $this->saveViewsUiModal();

    // All filters set, save the view.
    $this->page->pressButton('edit-actions-submit');
    $this->webAssert->pageTextContains('The view Content has been saved.');

    // Test the view filters and results.
    $this->drupalGet($view_page_url);
    $this->webAssert->pageTextContains('article 1');
    $this->webAssert->pageTextContains('page 3');

    // Check limit: 10 instead of total 12.
    $this->assertAutocompleteFilterInput('title', 'a', 10);
    // Check results.
    $this->assertAutocompleteFilterInput('title', 'article', 6);
    $this->assertAutocompleteFilterSelection('title', 'article 1', 1, ['article 1'], ['article 2']);

    // Now we have 'article 1' title filter set for the results.
    // Check operator "=".
    $this->assertAutocompleteFilterInput('name', 'article', 0);
    // Check with filtered results.
    $this->assertAutocompleteFilterInput('name', 'article_author', 1);
    $this->assertAutocompleteFilterSelection('name', 'article_author', 1, ['article 1'], ['article 2']);

    // Check Auto submit.
    $this->drupalGet($view_page_url);
    $this->assertAutocompleteFilterInput('combine', 'ag', 7);
    $this->assertAutocompleteFilterSelection('combine', 'page_author', 6, ['page 6'], ['article 1'], TRUE);

    // Check with Contextual filter.
    $this->drupalGet($view_page_url . '/1');
    $this->assertAutocompleteFilterInput('title', 'a', 1);
    $this->assertAutocompleteFilterInput('name', 'article_author', 1);
    $this->assertAutocompleteFilterInput('combine', 'art', 2);

    // Check dependent filters.
    $this->drupalGet($view_page_url);
    $this->assertAutocompleteFilterInput('title', 'icl', 6);
    $this->assertAutocompleteFilterSelectSuggestion('title', 'article 2');
    $this->assertAutocompleteFilterInput('name', 'article_author', 1);
    // With "autocomplete_dependent" enabled.
    $this->assertAutocompleteFilterInput('combine', 'art', 2, ['article 2', 'article_author']);
    // Without "autocomplete_dependent" enabled.
    $this->assertAutocompleteFilterInput('title', 'a', 10);

    // Check formatted suggestions.
    $this->drupalGet($view_admin_url);
    // Update existing title filter.
    $this->page->clickLink('Content: Title (exposed)');
    $this->webAssert->waitForField('options[expose][autocomplete_filter]');
    $this->page->uncheckField('options[expose][autocomplete_raw_dropdown]');
    $this->page->uncheckField('options[expose][autocomplete_raw_suggestion]');
    $this->assertTrue($this->page->hasUncheckedField('options[expose][autocomplete_raw_suggestion]'));
    $this->saveViewsUiModal();
    $this->page->pressButton('edit-actions-submit');

    $this->drupalGet($view_page_url);
    // It seems Drupal Gitlab CI fails to find full html, trying without html.
    // For example to look for entire link html
    // '<a href="/node/1s2" hreflang="en">page 6</a>'.
    // It does not fail testing locally.
    $this->assertAutocompleteFilterInput('title', 'pa', 6, ['/node/7', '/node/12']);
    // As the suggestion is a link, by clicking we will get to node page instead
    // of just updating the filter value.
    $this->assertAutocompleteFilterSelectSuggestion('title', 'page 3');
    $this->webAssert->addressEquals('node/9');
  }

  /**
   * Get the UI Autocomplete suggestion wrapper id, for multiple autocomplete.
   *
   * @param string $filter_name
   *   The autocomplete filter input name.
   *
   * @internal
   */
  protected function getUiAutocompleteElementId($filter_name): int {
    switch ($filter_name) {
      case 'title':
        return 1;

      case 'name':
        return 2;

      case 'combine':
        return 3;

      default:
        return 1;

    }
  }

  /**
   * Asserts that Autocomplete filter works properly.
   *
   * @param string $filter_name
   *   The autocomplete filter input name.
   * @param string $value
   *   The value to set for autocomplete filter input.
   * @param int $suggestions_count
   *   The number of autocomplete suggestions found.
   * @param array $expected_suggestions
   *   An array of expected results after filter.
   *
   * @internal
   */
  protected function assertAutocompleteFilterInput(string $filter_name, string $value, int $suggestions_count, array $expected_suggestions = []): void {
    $ui_autocomplete_selector = '#ui-id-' . $this->getUiAutocompleteElementId($filter_name) . '.ui-autocomplete';
    $filter = $this->page->findField($filter_name);
    // Reset first for successive input changes.
    if (!empty($filter->getValue())) {
      $filter->setValue('');
      $this->getSession()->getDriver()->keyDown($filter->getXpath(), ' ');
      $this->page->waitFor(3, function () use ($ui_autocomplete_selector) {
        return ($this->page->find('css', $ui_autocomplete_selector)->isVisible() === FALSE);
      });
    }

    $filter->setValue($value);
    $this->getSession()->getDriver()->keyDown($filter->getXpath(), ' ');
    $this->webAssert->waitForElementVisible('css', $ui_autocomplete_selector);

    // Check the autocomplete results.
    $results = $this->page->findAll('css', $ui_autocomplete_selector . ' li');
    $this->assertCount($suggestions_count, $results);

    foreach ($expected_suggestions as $expected_suggestion) {
      $this->webAssert->elementContains('css', $ui_autocomplete_selector, $expected_suggestion);
    }
  }

  /**
   * Asserts Autocomplete suggestion selection.
   *
   * @param string $filter_name
   *   The autocomplete filter input name.
   * @param string $selection_value
   *   The value to be selected form autocomplete suggestions.
   *
   * @internal
   */
  protected function assertAutocompleteFilterSelectSuggestion(string $filter_name, string $selection_value): void {
    $selection_xpath = '//ul[@id="ui-id-' . $this->getUiAutocompleteElementId($filter_name) . '"][contains(@class, "ui-autocomplete")]/li//a[text() = "' . $selection_value . '"]';
    $this->webAssert->elementExists('xpath', $selection_xpath)->click();
  }

  /**
   * Asserts that Autocomplete selection works properly.
   *
   * @param string $filter_name
   *   The autocomplete filter input name.
   * @param string $selection_value
   *   The value to be selected form autocomplete suggestions.
   * @param int $results_count
   *   The number of results found after filter applied.
   * @param array $expected_results
   *   An array of expected results after filter.
   * @param array $not_expected_results
   *   An array of not expected results after filter.
   * @param bool $auto_submit
   *   A boolean indicating if the filter has auto submit enabled.
   *
   * @internal
   */
  protected function assertAutocompleteFilterSelection(string $filter_name, string $selection_value, int $results_count, array $expected_results = [], array $not_expected_results = [], bool $auto_submit = FALSE): void {
    $this->assertAutocompleteFilterSelectSuggestion($filter_name, $selection_value);

    // Submit exposed form.
    if (!$auto_submit) {
      $this->page->pressButton('Filter');
    }

    $results = $this->page->findAll('css', '#views-form-content-page-1 td.views-field-title');
    $this->assertCount($results_count, $results);

    foreach ($expected_results as $expected_result) {
      $this->webAssert->pageTextContains($expected_result);
    }
    foreach ($not_expected_results as $not_expected_result) {
      $this->webAssert->pageTextNotContains($not_expected_result);
    }
  }

  /**
   * Adds a Views new filter by a given filter name identifier.
   *
   * @param string $filter_name_selector
   *   The autocomplete filter name identifier.
   *
   * @internal
   */
  protected function addViewsAutocompleteFilter(string $filter_name_selector): void {
    $this->page->clickLink('views-add-filter');
    $this->webAssert->waitForField('override[controls][options_search]');
    $this->page->findField($filter_name_selector)->click();
    $this->page->find('css', '.ui-dialog .ui-dialog-buttonpane')
      ->pressButton('Add and configure filter criteria');
    // Test the exposed filter options show up correctly.
    $this->webAssert->waitForField('options[expose_button][checkbox][checkbox]');
    $this->page->findField('options[expose_button][checkbox][checkbox]')->click();
    $this->assertTrue($this->page->hasCheckedField('options[expose_button][checkbox][checkbox]'));
    $this->webAssert->waitForField('options[expose][autocomplete_filter]');
    $this->page->checkField('options[expose][autocomplete_filter]');
  }

  /**
   * Save Views UI modal.
   *
   * @internal
   */
  protected function saveViewsUiModal(): void {
    $this->page->find('css', '.ui-dialog .ui-dialog-buttonpane')->pressButton('Apply');
    $this->webAssert->assertWaitOnAjaxRequest();
    $this->webAssert->waitForElementRemoved('css', '.ui-dialog');
  }

}
