<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Test Conditional Fields Number Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldNumberTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  use RandomGeneratorTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'conditional_fields',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/number_integer/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'number_integer';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * The value that trigger dependency.
   *
   * @var string
   */
  protected $validValue = '2019';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldSelector = '[name="field_' . $this->fieldName . '[0][value]"]';

    FieldStorageConfig::create([
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'type' => 'integer',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => [
        'min' => '',
        'max' => '',
        'prefix' => '',
      ],
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_' . $this->fieldName, [
        'type' => 'number',
        'settings' => [
          'prefix_suffix' => FALSE,
        ],
      ])
      ->save();
  }

  /**
   * Tests creating Conditional Field: Visible if has value from widget.
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      'field_' . $this->fieldName . '[0][value]' => $this->validValue,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is not visible');

    // Change the number field that should not show the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '05-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is not visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is visible');

    // Set wrong value for number field to hide the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '07-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is not visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '^2019$',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is not visible');

    // Change the number field that should not show the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '05-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is not visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is visible');

    // Set wrong value for number field to hide the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '07-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is not visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => "2017\r\n2019",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change the number field that should not show the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '05-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Set wrong value for number field to hide the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '07-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => "2017\r\n2019",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is not visible');

    // Change the number field that should not show the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '05-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is not visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is visible');

    // Set wrong value for number field to hide the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '07-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is not visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => "2017\r\n2019",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change the number field that should not show the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '05-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    // Set wrong value for number field to hide the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '07-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '05. Article Body field is not visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '06. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => "2017\r\n2019",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change the number field that should not show the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '05-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Set wrong value for number field to hide the body.
    $this->changeField($this->fieldSelector, mt_rand(10, 100));
    $this->createScreenshot($this->screenshotPath . '07-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', '!empty');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'empty');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, '!visible', '!empty');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' !visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, '!visible', 'empty');
    $this->createScreenshot($this->screenshotPath . '01-testNumberInteger-testVisibleValueWidget.png');

    $this->createScreenshot($this->screenshotPath . '02-testNumberInteger-testVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testNumberInteger-testVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body field_' . $this->fieldName . ' !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change the number field to show the body.
    $this->changeField($this->fieldSelector, $this->validValue);
    $this->createScreenshot($this->screenshotPath . '06-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Set an empty value to hide body.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testNumberInteger-testVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

}
