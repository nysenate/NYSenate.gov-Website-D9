<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Language Select Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldLanguageSelectTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'conditional_fields',
    'language',
    'node',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/language_select/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'langcode';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The default language code.
   *
   * @var string
   */
  protected $defaultLanguage;

  /**
   * An array with Not specified and Not applicable language codes.
   *
   * @var array
   */
  protected $langcodes = ['und', 'zxx'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldSelector = "[name=\"{$this->fieldName}[0][value]\"]";

    // Get the default language which will trigger the dependency.
    $this->defaultLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Enable language selector on node creation page.
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
      ->setLanguageAlterable(TRUE)
      ->setDefaultLangcode($this->defaultLanguage)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      $this->fieldName . '[0][value]' => $this->defaultLanguage,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      "regex" => '^' . $this->langcodes[0] . '$',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      "values" => implode("\r\n", $this->langcodes),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      "values" => implode("\r\n", $this->langcodes),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      "values" => implode("\r\n", $this->langcodes),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '01. Article Body field is not visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', $this->fieldName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-language-select-add-filed-conditions.png');

    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      "values" => implode("\r\n", $this->langcodes),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    $this->createScreenshot($this->screenshotPath . '02-language-select-post-add-list-options-filed-conditions.png');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->createScreenshot($this->screenshotPath . '03-language-select-submit-list-options-filed-conditions.png');
    $this->assertSession()->pageTextContains('body ' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->createScreenshot($this->screenshotPath . '04-language-select-body-visible-when-controlled-field-has-default-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '01. Article Body field is visible');

    // Change a select value set that should not show the body.
    $this->changeField($this->fieldSelector, $this->langcodes[0]);
    $this->createScreenshot($this->screenshotPath . '05-language-select-body-invisible-when-controlled-field-has-wrong-value.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a select value set to show the body.
    $this->changeField($this->fieldSelector, $this->defaultLanguage);
    $this->createScreenshot($this->screenshotPath . '06-language-select-body-visible-when-controlled-field-has-value.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

    // Change a select value set to hide the body again.
    $this->changeField($this->fieldSelector, $this->langcodes[1]);
    $this->createScreenshot($this->screenshotPath . '07-language-select-body-invisible-when-controlled-field-has-wrong-value-again.png');
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');
  }

}
