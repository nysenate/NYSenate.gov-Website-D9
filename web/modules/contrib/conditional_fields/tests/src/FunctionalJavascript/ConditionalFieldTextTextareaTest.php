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
class ConditionalFieldTextTextareaTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/textarea/';

  /**
   * The test's name to use in file names.
   *
   * @var string
   */
  protected $testName = 'TextTextarea';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'single_textarea';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * The target field name.
   *
   * @var string
   */
  protected $targetFieldName = 'body';

  /**
   * The target field wrapper selector.
   *
   * @var string
   */
  protected $targetFieldWrapp = '';

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
  protected function setUp(): void {
    parent::setUp();

    $this->fieldSelector = '[name="field_' . $this->fieldName . '[0][value]"]';
    $this->targetFieldWrapp = '.field--name-' . str_replace('_', '-', $this->targetFieldName);

    $this->fieldStorageDefinition = [
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text_long',
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
      ->setComponent('field_' . $this->fieldName, ['type' => 'text_textarea'])
      ->save();
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title Widget.
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = 'drupal test textarea';
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      'field_' . $this->fieldName . '[0][value]' => $text,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change field that should not show the body.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $text);
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = [$this->randomMachineName(), $this->randomMachineName()];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
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
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');
  }

  /**
   * Tests creating Conditional Field: Visible if has value from Title value.
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $text = ['drupal textarea text first', 'drupal textarea text second'];
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
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, 'https://drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Change field value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '.*data\=[\d]+.*',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $text_without_expresion = 'The field in not empty';
    $text_with_expresion = 'The field has data=2 text';

    $this->submitForm($data, 'Save settings');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change field that should not show the body.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, $text_without_expresion);
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $text_with_expresion);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');
    // Set up conditions.
    $text = ["first string", "second string"];
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
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');
    // Set up conditions.
    $text = [$this->randomMachineName(), $this->randomMachineName()];
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
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
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is invisible.
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');

    // Change field that should not show the body.
    $this->changeField($this->fieldSelector, $text[0]);
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Change field that should not show the body again.
    $this->changeField($this->fieldSelector, $text[1]);
    $this->createScreenshot($this->screenshotPath . '06-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is not visible');

    // Change field value to show the body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '07-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, 'Article \'' . $this->targetFieldName . '\' field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if isFilled.
   */
  public function testCreateConfigVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden($this->targetFieldWrapp, 0, 'Article \'' . $this->targetFieldName . '\' field is visible');
    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible($this->targetFieldWrapp, 10, 'Article \'' . $this->targetFieldName . '\' field is not visible');
  }

  /**
   * Tests creating Conditional Field: inVisible if isFilled.
   */
  public function testCreateConfigInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains($this->targetFieldName . ' field_' . $this->fieldName . ' !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden($this->targetFieldWrapp, 0, 'Article \'' . $this->targetFieldName . '\' field is visible');
    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible($this->targetFieldWrapp, 10, 'Article \'' . $this->targetFieldName . '\' field is not visible');
  }

}
