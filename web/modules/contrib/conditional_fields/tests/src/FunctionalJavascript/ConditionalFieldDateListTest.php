<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Test Conditional Fields States.
 *
 * @group conditional_fields
 */
class ConditionalFieldDateListTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/datelist/';

  /**
   * The test's name to use in file names.
   *
   * @var string
   */
  protected $testName = 'DateList';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_datelist';

  /**
   * Control field selector.
   *
   * @var array
   */
  protected $fieldSelectors;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldSelectors = [
      'day' => "[name=\"{$this->fieldName}[0][value][day]\"]",
      'month' => "[name=\"{$this->fieldName}[0][value][month]\"]",
      'year' => "[name=\"{$this->fieldName}[0][value][year]\"]",
    ];
    $fieldStorageDefinition = [
      'field_name'  => $this->fieldName,
      'entity_type' => 'node',
      'type'        => 'datetime',
      'settings' => ['datetime_type' => 'date'],
    ];
    $fieldStorage = FieldStorageConfig::create($fieldStorageDefinition);
    $fieldStorage->save();

    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle'        => 'article',
    ]);
    $field->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldName, [
        'type' => 'datetime_datelist',
      ])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $date = new DrupalDateTime();
    $day = $date->format('j');
    $wrong_day = (int) $day + 1;
    $month = $date->format('n');
    $year = $date->format('Y');

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldName . '[0][value][day]' => $day,
      $this->fieldName . '[0][value][month]' => $month,
      $this->fieldName . '[0][value][year]' => $year,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');
    $this->changeField($this->fieldSelectors['month'], $month);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelectors['year'], $year);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change a date that should not show the body again.
    $this->changeField($this->fieldSelectors['day'], $wrong_day);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $day = '11';
    $month = '12';
    $month_wrong = '5';
    $year = '2019';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '[\d]{4}-[12]{2}-[\d]{2}',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');
    $this->changeField($this->fieldSelectors['month'], $month);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelectors['year'], $year);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change a date that should not show the body again.
    $this->changeField($this->fieldSelectors['day'], $month_wrong);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $date = new DrupalDateTime();
    $day = $date->format('j');
    $wrong_day = (int) $day + 1;
    $month = $date->format('n');
    $year = $date->format('Y');

    $date_2 = new DrupalDateTime("-1 year");
    $day_2 = $date_2->format('j');
    $month_2 = $date_2->format('n');
    $year_2 = $date_2->format('Y');

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => implode("\r\n", [
        $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        $date_2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day);
    $this->changeField($this->fieldSelectors['month'], $month);
    $this->changeField($this->fieldSelectors['year'], $year);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');
    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day_2);
    $this->changeField($this->fieldSelectors['month'], $month_2);
    $this->changeField($this->fieldSelectors['year'], $year_2);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a date that should not show the body again.
    $this->changeField($this->fieldSelectors['day'], $wrong_day);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $date = new DrupalDateTime();
    $date->createFromTimestamp(time());
    $day = $date->format('j');
    $wrong_day = (int) $day + 1;
    $month = $date->format('n');
    $year = $date->format('Y');

    $date_2 = new DrupalDateTime();
    $date_2->createFromTimestamp(strtotime("-1 year"));
    $day_2 = $date_2->format('j');
    $month_2 = $date_2->format('n');
    $year_2 = $date_2->format('Y');

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => implode("\r\n", [
        $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        $date_2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should be show the body.
    $this->changeField($this->fieldSelectors['day'], $day);
    $this->changeField($this->fieldSelectors['month'], $month);
    $this->changeField($this->fieldSelectors['year'], $year);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');
    // Change a date that should be show the body.
    $this->changeField($this->fieldSelectors['day'], $day_2);
    $this->changeField($this->fieldSelectors['month'], $month_2);
    $this->changeField($this->fieldSelectors['year'], $year_2);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change a date that should not show the body again.
    $this->changeField($this->fieldSelectors['day'], $wrong_day);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $date = new DrupalDateTime();
    $day = $date->format('j');
    $wrong_day = (int) $day + 1;
    $month = $date->format('n');
    $year = $date->format('Y');

    $date_2 = new DrupalDateTime();
    $date_2->createFromTimestamp(strtotime("-1 year"));
    $day_2 = $date_2->format('j');
    $month_2 = $date_2->format('n');
    $year_2 = $date_2->format('Y');

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => implode("\r\n", [
        $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        $date_2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day);
    $this->changeField($this->fieldSelectors['month'], $month);
    $this->changeField($this->fieldSelectors['year'], $year);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');
    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day_2);
    $this->changeField($this->fieldSelectors['month'], $month_2);
    $this->changeField($this->fieldSelectors['year'], $year_2);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelectors['day'], $wrong_day);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $date = new DrupalDateTime();
    $day = $date->format('j');
    $wrong_day = (int) $day + 1;
    $month = $date->format('n');
    $year = $date->format('Y');

    $date_2 = new DrupalDateTime();
    $date_2->createFromTimestamp(strtotime("-1 year"));
    $day_2 = $date_2->format('j');
    $month_2 = $date_2->format('n');
    $year_2 = $date_2->format('Y');

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => implode("\r\n", [
        $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        $date_2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day);
    $this->changeField($this->fieldSelectors['month'], $month);
    $this->changeField($this->fieldSelectors['year'], $year);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');
    // Change a date that should not show the body.
    $this->changeField($this->fieldSelectors['day'], $day_2);
    $this->changeField($this->fieldSelectors['month'], $month_2);
    $this->changeField($this->fieldSelectors['year'], $year_2);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Check that the field Body is not visible.
    $this->changeField($this->fieldSelectors['day'], $wrong_day);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

}
