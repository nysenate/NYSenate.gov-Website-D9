<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields SelectMultiple Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldSelectMultipleTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'conditional_fields',
    'node',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/select_multiple/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_options';

  /**
   * Control field selector.
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
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldSelector = "[name=\"field_{$this->fieldName}[]\"]";
    $this->fieldStorageDefinition = [
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'type' => 'list_integer',
      'cardinality' => -1,
      'settings' => [
        'allowed_values' => ['One', 'Two', 'Three'],
      ],
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
    ]);
    $this->field->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_' . $this->fieldName, [
        'type' => 'options_select',
      ])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      'field_' . $this->fieldName . '[]' => [0, 1],
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot($this->screenshotPath . '05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot($this->screenshotPath . '06-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot($this->screenshotPath . '07-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '[0|1]',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot($this->screenshotPath . '05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot($this->screenshotPath . '06-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [3]);
    $this->createScreenshot($this->screenshotPath . '07-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 3]);
    $this->createScreenshot($this->screenshotPath . '08-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot($this->screenshotPath . '09-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => "0\r\n1",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot($this->screenshotPath . '05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0, 3]);
    $this->createScreenshot($this->screenshotPath . '06-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot($this->screenshotPath . '07-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot($this->screenshotPath . '08-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => "0\r\n1",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot($this->screenshotPath . '05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should be show the body.
    $this->changeField($this->fieldSelector, [0, 3]);
    $this->createScreenshot($this->screenshotPath . '06-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [3]);
    $this->createScreenshot($this->screenshotPath . '07-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot($this->screenshotPath . '08-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot($this->screenshotPath . '09-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => "0\r\n1",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot($this->screenshotPath . '05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should be show the body.
    $this->changeField($this->fieldSelector, [0, 3]);
    $this->createScreenshot($this->screenshotPath . '06-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [3]);
    $this->createScreenshot($this->screenshotPath . '07-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot($this->screenshotPath . '08-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot($this->screenshotPath . '09-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-add-list-options-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => "0\r\n1",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-body-invisible-when-controlled-field-has-no-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [0]);
    $this->createScreenshot($this->screenshotPath . '05-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should be show the body.
    $this->changeField($this->fieldSelector, [0, 3]);
    $this->createScreenshot($this->screenshotPath . '06-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, [3]);
    $this->createScreenshot($this->screenshotPath . '07-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, [0, 1]);
    $this->createScreenshot($this->screenshotPath . '08-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, ['_none']);
    $this->createScreenshot($this->screenshotPath . '09-body-invisible-when-controlled-field-has-no-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
  }

  /**
   * Helper to change Field value with Javascript.
   */
  protected function changeField($selector, $value = '') {
    $value = json_encode($value);
    $this->getSession()->executeScript("jQuery('{$selector}').val({$value}).trigger('keyup').trigger('change');");
  }

}
