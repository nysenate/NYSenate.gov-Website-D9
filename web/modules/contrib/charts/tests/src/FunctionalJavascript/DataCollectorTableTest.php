<?php

namespace Drupal\Tests\charts\FunctionalJavascript;

use Drupal\charts_test\Form\DataCollectorTableTestForm;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\charts\Traits\ConfigUpdateTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the data collector table element.
 *
 * @group charts
 */
class DataCollectorTableTest extends WebDriverTestBase {

  use ConfigUpdateTrait;
  use StringTranslationTrait;

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * List modules.
   *
   * @var array
   */
  protected static $modules = [
    'charts',
    'charts_test',
  ];

  const TABLE_SELECTOR = 'table[data-drupal-selector="edit-series-data-collector-table"]';

  const TABLE_ROW_SELECTOR = 'table[data-drupal-selector="edit-series-data-collector-table"] tr.data-collector-table--row';

  const TABLE_COLUMN_SELECTOR = 'table[data-drupal-selector="edit-series-data-collector-table"] tbody tr:nth-child(1) td:not(.data-collector-table--row--delete)';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->updateFooConfiguration('bar');
  }

  /**
   * Tests the data collector table.
   */
  public function testDataCollectorTable() {
    $this->drupalGet('/charts_test/data_collector_table_test_form');
    $table_id_selector = static::TABLE_SELECTOR;

    // Count generated rows.
    $rows = $this->cssSelect(static::TABLE_ROW_SELECTOR);
    $this->assertTrue(count($rows) === DataCollectorTableTestForm::INITIAL_ROWS, 'Expected rows were found.');

    // Count generated columns.
    // Only using the first row to count columns.
    $columns = $this->cssSelect(static::TABLE_COLUMN_SELECTOR);
    $this->assertTrue(count($columns) === DataCollectorTableTestForm::INITIAL_COLUMNS, 'Expected columns were found.');

    // Fill the table.
    $cell_input_selector = $table_id_selector . ' tr.data-collector-table--row td > .data-collector-table--row--cell .form-text';
    $cell_inputs = $this->cssSelect($cell_input_selector);
    $this->assertNotEmpty($cell_inputs, $this->t('@count inputs were found', [
      '@count' => count($cell_inputs),
    ]));
    $this->fillInputs($cell_inputs);

    // Test adding a row.
    $this->doTableOperation('add', 'row');
    // New row should not have any data.
    $this->assertNewCellIsEmpty('row');

    // Test adding a column.
    $this->doTableOperation('add', 'column');
    // New column should not have any data.
    $this->assertNewCellIsEmpty('column');

    // Delete second and third row.
    $this->doTableOperation('delete', 'row', [2, 3]);
    // Remove middle column.
    $this->doTableOperation('delete', 'column', [2]);

    // Import csv from resources - not passing complaining file not found.
    // $file_input_selector = 'files[series]';
    // $file_input = $this->getSession()
    // ->getPage()
    // ->find('css', '[name="' . $file_input_selector . '"]');
    // $file_input->setValue($this->getResourcesUrl() .
    // '/csv/first_column.csv');
    // $this->prepareRequest();
    // code/web/modules/custom/charts/tests/resources/csv/first_row.csv.
    // $button_selector = 'details[data-drupal-selector="edit-import"].
    // Input[value="Upload CSV"]';
    // $page = $this->getSession()->getPage();
    // $path = $this->getResourcesUrl() . '/csv/first_column.csv';
    // $page->attachFileToField($file_input_selector, $path);
    // $this->pressAjaxButton($button_selector);
    // $web_assert = $this->assertSession();
    // $web_assert->assertWaitOnAjaxRequest(20000);
    // $page = $this->getSession()->getPage();
    // Count rows
    // $targets = $page->findAll('css', static::TABLE_ROW_SELECTOR);
    // $this->assertTrue(count($targets) === 7);
    // Count columns
    // $targets = $page->findAll('css', static::TABLE_COLUMN_SELECTOR);
    // $this->assertTrue(count($targets) === 3);
    // Delete all the rows and columns - skip for now.
    // Confirm that only one row and column left - skip.
    // Submit the form and verify the submitted data. - skip.
  }

  /**
   * Tests the default colors pn the "chart_data_collector_table" element.
   */
  public function testColorDefaultColors() {
    $chart_config = \Drupal::config('charts.settings');
    $default_colors = $chart_config->get('charts_default_settings.display.colors');
    $this->drupalGet('/charts_test/data_collector_table_test_form');
    $page = $this->getSession()->getPage();

    // Get the first row, then inside the first row get the color input and
    // check its value.
    $first_row_color_input = $page->find('css', static::TABLE_ROW_SELECTOR . ':first-child td:nth-child(2) input[type="color"]');
    $this->assertEquals($default_colors[0], $first_row_color_input->getValue());

    // Adding one column.
    $this->doTableOperation('add', 'column');

    // Checking if the added column also has the expected color.
    $first_row_color_input = $page->find('css', static::TABLE_ROW_SELECTOR . ':first-child td:nth-child(3) input[type="color"]');
    $this->assertEquals($default_colors[1], $first_row_color_input->getValue());
  }

  /**
   * Do table operation.
   *
   * @param string $operation
   *   The operation.
   * @param string $on
   *   The element.
   * @param array $positions
   *   The position.
   */
  protected function doTableOperation($operation, $on, array $positions = []) {
    $value = ucfirst($operation) . ' ' . $on;

    if ($operation === 'add') {
      $selector = static::TABLE_SELECTOR . ' input[value="' . $value . '"]';
      $this->pressAjaxButton($selector);

      if ($on === 'row') {
        $this->assertRowsIncreased();
      }
      else {
        $this->assertColumnsIncreased();
      }
    }
    else {
      $on_row = $on === 'row';
      if ($on_row) {
        $counter = DataCollectorTableTestForm::INITIAL_ROWS + 1;
        $locator = static::TABLE_ROW_SELECTOR;
      }
      else {
        $counter = DataCollectorTableTestForm::INITIAL_COLUMNS + 1;
        $locator = static::TABLE_COLUMN_SELECTOR;
      }
      foreach ($positions as $position) {
        if ($on_row) {
          $button_selector = static::TABLE_ROW_SELECTOR . ':nth-child(' . $position . ') .data-collector-table--row--delete input[value="Delete row"]';
        }
        else {
          $button_selector = static::TABLE_SELECTOR . ' .data-collector-table--column-deletes-row';
          $button_selector .= ' .data-collector-table--column--delete:nth-child(' . $position . ') input[value="Delete column"]';
        }
        $this->pressAjaxButton($button_selector);
        $this->assertDeletionOperation($counter, $locator);
      }
    }
  }

  /**
   * Assert rows.
   */
  protected function assertRowsIncreased() {
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->waitFor(10, function ($page) {
      $expected_rows = DataCollectorTableTestForm::INITIAL_ROWS + 1;
      $rows = $page->findAll('css', static::TABLE_ROW_SELECTOR);
      return count($rows) === $expected_rows;
    }), 'Expected rows were increased by one after add row click.');
  }

  /**
   * Assert columns.
   */
  protected function assertColumnsIncreased() {
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->waitFor(10, function ($page) {
      $expected_rows = DataCollectorTableTestForm::INITIAL_COLUMNS + 1;
      $columns = $page->findAll('css', static::TABLE_COLUMN_SELECTOR);
      return count($columns) === $expected_rows;
    }), 'Expected columns were increased by one after add column click.');
  }

  /**
   * Assert new cell.
   *
   * @param string $on
   *   The element.
   */
  protected function assertNewCellIsEmpty(string $on) {
    $page = $this->getSession()->getPage();
    if ($on === 'row') {
      $counter = DataCollectorTableTestForm::INITIAL_ROWS + 1;
      $selector = static::TABLE_ROW_SELECTOR . ':nth-child(' . $counter . ') td:first-child input';
    }
    else {
      $counter = DataCollectorTableTestForm::INITIAL_COLUMNS + 1;
      $selector = static::TABLE_COLUMN_SELECTOR . ':nth-child(' . $counter . ') input';
    }
    $cell_input = $page->find('css', $selector);
    $this->assertEmpty($cell_input->getValue(), 'Added row cells are empty.');
  }

  /**
   * Assert Deletion operation.
   *
   * @param int $current_count
   *   Current count.
   * @param string $locator
   *   The locator.
   */
  protected function assertDeletionOperation(&$current_count, $locator) {
    $web_assert = $this->assertSession();
    $web_assert->assertWaitOnAjaxRequest();
    $current_count--;
    $page = $this->getSession()->getPage();
    $targets = $page->findAll('css', $locator);
    $this->assertTrue(count($targets) === $current_count);
  }

  /**
   * Press ajax button.
   *
   * @param string $selector
   *   The selector.
   */
  protected function pressAjaxButton($selector) {
    $button = $this->assertSession()->waitForElementVisible('css', $selector);
    $this->assertNotEmpty($button);
    $button->click();
  }

  /**
   * Fill inputs.
   *
   * @param array $inputs
   *   Input to fill.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The node element.
   */
  protected function fillInputs(array $inputs) {
    /** @var \Behat\Mink\Element\NodeElement[] $inputs */
    foreach ($inputs as $input) {
      $value = rand(0, count($inputs));
      $input->setValue($value);
    }
    return $inputs;
  }

  /**
   * Get url of resources.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The url.
   */
  protected function getResourcesUrl() {
    $resources_path = \Drupal::service('extension.list.module')->getPath('charts') . '/tests/resources';
    return Url::fromUri('internal:/' . $resources_path)->toString();
  }

}
