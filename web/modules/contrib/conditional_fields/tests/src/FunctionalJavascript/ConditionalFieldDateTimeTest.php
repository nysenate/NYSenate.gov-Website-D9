<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
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
class ConditionalFieldDateTimeTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/datetime/';

  /**
   * An array of display options to pass to entity_get_display()
   *
   * @var array
   */
  protected $displayOptions;

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_datetime';

  /**
   * Control field selector.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldSelector = "[name=\"{$this->fieldName}[0][value][date]\"]";
    $fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'date'],
    ];
    $fieldStorage = FieldStorageConfig::create($fieldStorageDefinition);
    $fieldStorage->save();

    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'article',
    ]);
    $field->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldName, [
        'type' => 'datetime_default',
      ])
      ->save();

    $defaultSettings = [
      'timezone_override' => '',
    ];

    $this->displayOptions = [
      'type' => 'datetime_default',
      'label' => 'hidden',
      'settings' => ['format_type' => 'medium'] + $defaultSettings,
    ];
    $view_display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($field->getTargetEntityTypeId() . '.' . $field->getTargetBundle() . '.' . 'full');

    if (!$view_display) {
      $view_display = EntityViewDisplay::create([
        'targetEntityType' => $field->getTargetEntityTypeId(),
        'bundle' => $field->getTargetBundle(),
        'mode' => 'full',
        'status' => TRUE,
      ]);
    }
    if ($view_display instanceof EntityDisplayInterface) {
      $view_display->setComponent($this->fieldName, $this->displayOptions)
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testDateTimeVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldName . '[0][value][date]' => DrupalDateTime::createFromTimestamp(\Drupal::time()->getRequestTime())->format('m-d-Y'),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testDateTimeVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testDateTimeVisibleValueWidget.png');
    $this->assertSession()
      ->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '04-testDateTimeVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, DrupalDateTime::createFromTimestamp(\Drupal::time()->getRequestTime())->format('Y-m-d'));
    $this->createScreenshot($this->screenshotPath . '05-testDateTimeVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a date that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-testDateTimeVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $date = new DrupalDateTime();
    $date_formatted = $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testDateTimeVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '^' . $date_formatted . '$',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testDateTimeVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testDateTimeVisibleValueWidget.png');
    $this->assertSession()
      ->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '04-testDateTimeVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $date_formatted);
    $this->createScreenshot($this->screenshotPath . '05-testDateTimeVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a date that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-testDateTimeVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $date = new DrupalDateTime();
    $date2 = new DrupalDateTime("-1 year");

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testDateTime ' . __FUNCTION__ . '.png');

    // Set up conditions.
    $dates = [
      $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      $date2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
    ];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => implode("\r\n", $dates),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testDateTime ' . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testDateTime ' . __FUNCTION__ . '.png');
    $this->assertSession()
      ->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testDateTime ' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testDateTime ' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[0]);
    $this->createScreenshot($this->screenshotPath . '06-testDateTime ' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[1]);
    $this->createScreenshot($this->screenshotPath . '07-testDateTime ' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    // Change a date value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testDateTime ' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '0.5Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $date = new DrupalDateTime();
    $date2 = new DrupalDateTime("-1 year");

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testDateTimeVisibleValueOr.png');

    // Set up conditions.
    $dates = [
      $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      $date2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
    ];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => implode("\r\n", $dates),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testDateTimeVisibleValueOr.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testDateTimeVisibleValueOr.png');
    $this->assertSession()
      ->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testDateTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testDateTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[0]);
    $this->createScreenshot($this->screenshotPath . '06-testDateTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[1]);
    $this->createScreenshot($this->screenshotPath . '07-testDateTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change a date value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testDateTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '0.5 Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $date = new DrupalDateTime();
    $date2 = new DrupalDateTime("-1 year");

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testDateTime' . __FUNCTION__ . '.png');

    // Set up conditions.
    $dates = [
      $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      $date2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
    ];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => implode("\r\n", $dates),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testDateTime' . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testDateTime' . __FUNCTION__ . '.png');
    $this->assertSession()
      ->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[0]);
    $this->createScreenshot($this->screenshotPath . '06-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[1]);
    $this->createScreenshot($this->screenshotPath . '07-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    // Change a date value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '0.5 Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $date = new DrupalDateTime();
    $date2 = new DrupalDateTime("-1 year");

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testDateTime' . __FUNCTION__ . '.png');

    // Set up conditions.
    $dates = [
      $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      $date2->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
    ];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => implode("\r\n", $dates),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testDateTime' . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testDateTime' . __FUNCTION__ . '.png');
    $this->assertSession()
      ->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a date that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[0]);
    $this->createScreenshot($this->screenshotPath . '06-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change a date value to show the body.
    $this->changeField($this->fieldSelector, $dates[1]);
    $this->createScreenshot($this->screenshotPath . '07-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change a date value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testDateTime' . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '0.5 Article Body field is visible');
  }

}
