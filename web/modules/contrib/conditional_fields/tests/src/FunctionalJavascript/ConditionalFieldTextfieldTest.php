<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldFilledEmptyInterface;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Text Handler.
 *
 * @group conditional_fields
 */
class ConditionalFieldTextfieldTest extends ConditionalFieldTestBase implements
    ConditionalFieldValueInterface,
    ConditionalFieldFilledEmptyInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/text/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'single_textfield';

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
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldSelector = '[name="field_' . $this->fieldName . '[0][value]"]';
    $this->targetFieldWrapp = '.field--name-' . str_replace('_', '-', $this->targetFieldName);

    $fieldStorageDefinition = [
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => 1,
    ];
    $fieldStorage = FieldStorageConfig::create($fieldStorageDefinition);
    $fieldStorage->save();

    FieldConfig::create([
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_' . $this->fieldName, ['type' => 'text_textfield'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    // Set up conditions.
    $text = $this->getRandomGenerator()->word(8);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      'field_' . $this->fieldName . '[0][value]' => $text,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');
    $this->changeField($this->fieldSelector, $text);
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $text . 'a');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

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

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrapp);

    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');
    $this->changeField($this->fieldSelector, $text_without_expresion);
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '02. Article Body field is visible');
    $this->changeField($this->fieldSelector, $text_with_expresion);
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '03. Article Body field is not visible');

  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $text_1 = $this->getRandomGenerator()->word(7);
    $text_2 = $this->getRandomGenerator()->word(7);

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => implode("\r\n", [
        $text_1,
        $text_2,
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrapp);

    $text_false = implode(' ', [$text_1, $text_2]);

    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_false);
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_1);
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '03. Article Body field is visible');

    // Change a value value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '04. Article Body field is visible');

  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    // Random term id to check necessary value.
    $text1 = $this->getRandomGenerator()->word(8);
    $text2 = $this->getRandomGenerator()->word(7);

    // Set up conditions.
    $values = implode("\r\n", [$text1, $text2]);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => $values,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');

    // Change value that should not show the body.
    $this->changeField($this->fieldSelector, 'wrong');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '02. Article Body field is visible');

    // Change a value value to show the body.
    $this->changeField($this->fieldSelector, $text1);
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '03. Article Body field is not visible');

    // Change a value value to show the body.
    $this->changeField($this->fieldSelector, $text2);
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '04. Article Body field is not visible');

    // Change a value value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $text_1 = $this->getRandomGenerator()->word(7);
    $text_2 = $this->getRandomGenerator()->word(7);

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => implode("\r\n", [
        $text_1,
        $text_2,
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');

    $this->waitUntilVisible($this->targetFieldWrapp, 0, '01. Article Body field is not visible');

    $this->changeField($this->fieldSelector, 'some-unique-text');
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '02. Article Body field is not visible');

    $this->changeField($this->fieldSelector, $text_1);
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '03. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_2);
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '04. Article Body field is visible');

    $this->changeField($this->fieldSelector, "");
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $text_1 = $this->getRandomGenerator()->word(7);
    $text_2 = $this->getRandomGenerator()->word(7);

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => implode("\n", [
        $text_1,
        $text_2,
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrapp);

    $text_false = 'same unique value';

    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_false);
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_1);
    $this->waitUntilVisible($this->targetFieldWrapp, 50, '03. Article Body field is not visible');

    $this->changeField($this->fieldSelector, "");
    $this->waitUntilHidden($this->targetFieldWrapp, 50, '04. Article Body field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if isFilled.
   */
  public function testVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible($this->targetFieldWrapp, 10, '02. Article Body field is not visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrapp, 10, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleEmpty() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'empty');
    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible empty');

    $this->drupalGet('node/add/article');

    $this->waitUntilVisible($this->targetFieldWrapp, 0, '01. Article Body field is not visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilHidden($this->targetFieldWrapp, 10, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilVisible($this->targetFieldWrapp, 10, '03. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleFilled() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, '!visible', '!empty');
    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' !visible !empty');

    $this->drupalGet('node/add/article');

    $this->waitUntilVisible($this->targetFieldWrapp, 0, '01. Article Body field is not visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilHidden($this->targetFieldWrapp, 10, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilVisible($this->targetFieldWrapp, 10, '03. Article Body field is  notvisible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden($this->targetFieldWrapp, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible($this->targetFieldWrapp, 10, '02. Article Body field is not visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrapp, 10, '03. Article Body field is not visible');
  }

}
