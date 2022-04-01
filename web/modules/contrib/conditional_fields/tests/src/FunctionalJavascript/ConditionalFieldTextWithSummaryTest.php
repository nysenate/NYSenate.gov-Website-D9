<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Text Handler.
 *
 * @group conditional_fields
 */
class ConditionalFieldTextWithSummaryTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/text_with_summary/';

  /**
   * The test's name to use in file names.
   *
   * @var string
   */
  protected $testName = 'TextWithSummary';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'single_text_with_summary';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * The field storage definition used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fieldSelector = '[name="field_' . $this->fieldName . '[0][value]"]';
    $this->fieldStorageDefinition = [
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text_with_summary',
      'cardinality' => 1,
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    FieldConfig::create([
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_' . $this->fieldName, ['type' => 'text_textarea_with_summary'])
      ->save();
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title Widget.
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = 'drupal test text_with_summary';
    $data = [
      '[name="condition"]' => 'value',
      '[name="values_set"]' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldSelector => $text,
      '[name="grouping"]' => 'AND',
      '[name="state"]' => 'visible',
      '[name="effect"]' => 'show',
    ];
    foreach ($data as $selector => $value) {
      $this->changeField($selector, $value);
    }

    $this->getSession()->wait(1000, '!jQuery.active');
    $this->getSession()->executeScript("jQuery('#conditional-field-edit-form').submit();");
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    $this->clickLink('Edit');
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change field that should not show the body.
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $text);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = ['drupal text_with_summary text first', 'drupal text_with_summary text second'];
    $text_values = implode("\r\n", $text);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => $text_values,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    $this->clickLink('Edit');
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'drupal.org');
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, implode('\r\n', $text));
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = ['drupal text_with_summary text first', 'drupal text_with_summary text second'];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => implode("\r\n", $text),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    $this->clickLink('Edit');
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '08-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '09-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '08. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = 'text_';
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => $text,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'wrong text');
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, 'text_correct');
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, 'wrong text');
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = ['drupal text_with_summary text first', 'drupal text_with_summary text second'];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => implode("\r\n", $text),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = ['drupal text_with_summary text first', 'drupal text_with_summary text second'];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => implode("\r\n", $text),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if isFilled.
   */
  public function testCreateConfigVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
  }

  /**
   * Tests creating Conditional Field: inVisible if isFilled.
   */
  public function testCreateConfigInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->fieldName, '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible('.field--name-body', 10, 'Article Body field is not visible');
  }

}
