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
class ConditionalFieldEmailTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface, ConditionalFieldFilledEmptyInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'conditional_fields',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/email/';

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
  protected $fieldName = 'test_email';

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
  protected function setUp() {
    parent::setUp();

    $this->fieldSelector = '[name="field_' . $this->fieldName . '[0][value]"]';

    FieldStorageConfig::create([
      'field_name' => 'field_' . $this->fieldName,
      'entity_type' => 'node',
      'type' => 'email',
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
        'type' => 'email_default',
        'settings' => [
          'prefix_suffix' => FALSE,
        ],
      ])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $email = 'test@drupal.org';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testEmailVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      'field_' . $this->fieldName . '[0][value]' => $email,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testEmailVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testEmailVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change a email that should not show the body.
    $this->changeField($this->fieldSelector, 'wrongmail@drupal.org');
    $this->createScreenshot($this->screenshotPath . '04-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 500, '01. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $email);
    $this->createScreenshot($this->screenshotPath . '05-testEmailVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 500, '02. Article Body field is not visible');

    // Change a email that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $email = 'test@drupal.org';
    $email_wrong = 'wrongmail@drupal.org';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testEmailVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '^test@.+\..+',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testEmailVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testEmailVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change a email that should not show the body.
    $this->changeField($this->fieldSelector, $email_wrong);
    $this->createScreenshot($this->screenshotPath . '04-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 500, '01. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $email);
    $this->createScreenshot($this->screenshotPath . '05-testEmailVisibleValueWidget.png');
    $this->waitUntilVisible('.field--name-body', 500, '02. Article Body field is not visible');

    // Change a email that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $email = 'first@drupal.org';
    $email_2 = 'second@drupal.org';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testEmailVisibleValueWidget.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => "{$email}\r\n{$email_2}",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testEmailVisibleValueWidget.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testEmailVisibleValueWidget.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Change a email that should not show the body.
    $this->createScreenshot($this->screenshotPath . '04-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 500, '01. Article Body field is visible');

    // Check that the field Body is visible.
    $this->changeField($this->fieldSelector, $email);
    $this->createScreenshot($this->screenshotPath . '05-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 500, '02. Article Body field is visible');

    // Check that the field Body is not visible.
    $this->changeField($this->fieldSelector, $email_2);
    $this->createScreenshot($this->screenshotPath . '05-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 500, '02. Article Body field is visible');

    // Change a email that should not show the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '06-testEmailVisibleValueWidget.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $email = 'test@drupal.org';
    $email2 = 'test2@drupal.org';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testEmailTimeVisibleValueOr.png');

    // Set up conditions.
    $emails = implode("\r\n", [$email, $email2]);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => $emails,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testEmailTimeVisibleValueOr.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testEmailTimeVisibleValueOr.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change email that should not show the body.
    $this->changeField($this->fieldSelector, 'wrongmail@drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a email value to show the body.
    $this->changeField($this->fieldSelector, $email);
    $this->createScreenshot($this->screenshotPath . '06-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 500, '03. Article Body field is not visible');

    // Change a email value to show the body.
    $this->changeField($this->fieldSelector, $email2);
    $this->createScreenshot($this->screenshotPath . '07-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change a email value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $email = 'test@drupal.org';
    $email2 = 'test2@drupal.org';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testEmailTimeVisibleValueOr.png');

    // Set up conditions.
    $emails = implode("\r\n", [$email, $email2]);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => $emails,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testEmailTimeVisibleValueOr.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testEmailTimeVisibleValueOr.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change email that should not show the body.
    $this->changeField($this->fieldSelector, 'wrongmail@drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a email value to show the body.
    $this->changeField($this->fieldSelector, $email);
    $this->createScreenshot($this->screenshotPath . '06-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 500, '03. Article Body field is visible');

    // Change a email value to show the body.
    $this->changeField($this->fieldSelector, $email2);
    $this->createScreenshot($this->screenshotPath . '07-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    // Change a email value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $email = 'test@drupal.org';
    $email2 = 'test2@drupal.org';

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-testEmailTimeVisibleValueOr.png');

    // Set up conditions.
    $emails = implode("\r\n", [$email, $email2]);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => $emails,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-testEmailTimeVisibleValueOr.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');

    $this->createScreenshot($this->screenshotPath . '03-testEmailTimeVisibleValueOr.png');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '04-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change email that should not show the body.
    $this->changeField($this->fieldSelector, 'wrongmail@drupal.org');
    $this->createScreenshot($this->screenshotPath . '05-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a email value to show the body.
    $this->changeField($this->fieldSelector, $email);
    $this->createScreenshot($this->screenshotPath . '06-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 500, '03. Article Body field is not visible');

    // Change a email value to show the body.
    $this->changeField($this->fieldSelector, $email2);
    $this->createScreenshot($this->screenshotPath . '07-testEmailTimeVisibleValueOr.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');

    // Change a email value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->createScreenshot($this->screenshotPath . '08-testEmailTimeVisibleValueOr.png');
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if isFilled.
   */
  public function testVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is not visible');
    $this->changeField($this->fieldSelector, 'test@drupal.org');
    $this->waitUntilVisible('.field--name-body', 10, '02. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->fieldName, 'visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilVisible('.field--name-body', 0, '01. Article Body field is visible');
    $this->changeField($this->fieldSelector, 'test@drupal.org');
    $this->waitUntilHidden('.field--name-body', 10, '02. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->fieldName, '!visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' !visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilVisible('.field--name-body', 0, '01. Article Body field is visible');
    $this->changeField($this->fieldSelector, 'test@drupal.org');
    $this->waitUntilHidden('.field--name-body', 10, '02. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->fieldName, '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body ' . 'field_' . $this->fieldName . ' !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is not visible');
    $this->changeField($this->fieldSelector, 'test@drupal.org');
    $this->waitUntilVisible('.field--name-body', 10, '02. Article Body field is visible');
  }

}
