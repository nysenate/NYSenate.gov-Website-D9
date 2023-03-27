<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Select Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldSelectTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

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
  protected $screenshotPath = 'sites/simpletest/conditional_fields/select/';

  /**
   * The field names used in the test.
   *
   * @var string[]
   */
  protected $fieldNames = [
    'select_single_entity_reference',
    'select_single_list_integer',
    'select_single_list_float',
    'select_single_list_string',
  ];

  /**
   * Jquery selectors of fields in a document.
   *
   * @var string[]
   */
  protected $fieldSelectors;

  /**
   * The field storage definitions used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinitions;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorages;

  /**
   * Fields to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fields;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    foreach ($this->fieldNames as $fieldName) {
      $this->fieldSelectors[$fieldName] = "[name=\"{$fieldName}\"]";
      $this->fieldStorageDefinitions[$fieldName] = [
        'field_name' => $fieldName,
        'entity_type' => 'node',
        // Cut 'select_single_' for getting field types.
        'type' => str_replace('select_single_', '', $fieldName),
        'cardinality' => 1,
      ];
    }
    // Define allowed values for each field type.
    $this->fieldStorageDefinitions['select_single_entity_reference']['settings']['target_type'] = 'user';
    $this->fieldStorageDefinitions['select_single_list_integer']['settings']['allowed_values'] = [
      1 => '1',
      2 => '2',
      3 => '3',
    ];
    $this->fieldStorageDefinitions['select_single_list_float']['settings']['allowed_values'] = [
      '1.5' => '1.5',
      '2.5' => '2.5',
      '3.5' => '3.5',
    ];
    $this->fieldStorageDefinitions['select_single_list_string']['settings']['allowed_values'] = [
      'one' => 'One',
      'two' => 'Two',
      'three' => 'Three',
    ];

    $entity_form_display = EntityFormDisplay::load('node.article.default');

    foreach ($this->fieldNames as $fieldName) {
      // Save field storage configurations.
      $this->fieldStorages[$fieldName] = FieldStorageConfig::create($this->fieldStorageDefinitions[$fieldName]);
      $this->fieldStorages[$fieldName]->save();

      // Create a field configuration.
      $this->fields[$fieldName] = FieldConfig::create([
        'field_storage' => $this->fieldStorages[$fieldName],
        'bundle' => 'article',
      ]);
      $this->fields[$fieldName]->save();

      // Set field form display settings for the field.
      $entity_form_display->setComponent($fieldName, ['type' => 'options_select']);
    }
    $entity_form_display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    $correct_values = [
      'select_single_entity_reference' => 1,
      'select_single_list_integer' => 1,
      'select_single_list_float' => 1.5,
      'select_single_list_string' => 'one',
    ];
    $wrong_values = [
      'select_single_entity_reference' => 3,
      'select_single_list_integer' => 3,
      'select_single_list_float' => 3.5,
      'select_single_list_string' => 'three',
    ];

    // Visit a ConditionalFields configuration page for Content bundles.
    foreach ($this->fieldNames as $fieldName) {
      $this->createCondition('body', $fieldName, 'visible', 'value');

      $this->createScreenshot($this->screenshotPath . '01-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Set up conditions.
      $data = [
        '[name="condition"]' => 'value',
        '[name="values_set"]' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
        $this->fieldSelectors[$fieldName] => $correct_values[$fieldName],
        '[name="grouping"]' => 'AND',
        '[name="state"]' => 'visible',
        '[name="effect"]' => 'show',
      ];
      foreach ($data as $selector => $value) {
        $this->changeField($selector, $value);
      }
      $this->getSession()->wait(1000, '!jQuery.active');
      $this->getSession()
        ->executeScript("jQuery('#conditional-field-edit-form').submit();");
      $this->createScreenshot($this->screenshotPath . '02-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Check if that configuration is saved.
      $this->drupalGet('admin/structure/types/manage/article/conditionals');
      $this->createScreenshot($this->screenshotPath . '03-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->assertSession()
        ->pageTextContains('body ' . $fieldName . ' visible value');

      // Visit Article Add form to check that conditions are applied.
      $this->drupalGet('node/add/article');

      // Check that the field Body is not visible.
      $this->createScreenshot($this->screenshotPath . '04-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

      // Change a select value set that should not show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $wrong_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

      // Change a select value set to show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $correct_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '06-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');

      // Change a select value set to hide the body again.
      $this->changeField($this->fieldSelectors[$fieldName], '_none');
      $this->createScreenshot($this->screenshotPath . '07-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');

      // Return back to ConditionalFields configuration page for Article CT.
      $this->drupalGet('admin/structure/conditional_fields/node/article');

      // Delete previous condition.
      $this->click('li > button > .dropbutton-arrow');
      $this->clickLink('Delete');
      $this->submitForm([], 'Confirm');
      $this->createScreenshot($this->screenshotPath . '08-' . $fieldName . '_' . __FUNCTION__ . '.png');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    $reg_patterns = [
      'select_single_entity_reference' => '[1]+',
      'select_single_list_integer' => '[1-2]+',
      'select_single_list_float' => '[1-2]+\.5',
      'select_single_list_string' => '^(one|two)$',
    ];

    $correct_values = [
      'select_single_entity_reference' => 1,
      'select_single_list_integer' => 1,
      'select_single_list_float' => 1.5,
      'select_single_list_string' => 'one',
    ];
    $wrong_values = [
      'select_single_entity_reference' => 3,
      'select_single_list_integer' => 3,
      'select_single_list_float' => 3.5,
      'select_single_list_string' => 'three',
    ];

    // Visit a ConditionalFields configuration page for Content bundles.
    foreach ($this->fieldNames as $fieldName) {
      $this->createCondition('body', $fieldName, 'visible', 'value');

      $this->createScreenshot($this->screenshotPath . '01-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Set up conditions.
      $data = [
        '[name="condition"]' => 'value',
        '[name="values_set"]' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
        '[name="regex"]' => $reg_patterns[$fieldName],
        '[name="grouping"]' => 'AND',
        '[name="state"]' => 'visible',
        '[name="effect"]' => 'show',
      ];
      foreach ($data as $selector => $value) {
        $this->changeField($selector, $value);
      }
      $this->getSession()->wait(1000, '!jQuery.active');
      $this->getSession()
        ->executeScript("jQuery('#conditional-field-edit-form').submit();");
      $this->createScreenshot($this->screenshotPath . '02-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Check if that configuration is saved.
      $this->drupalGet('admin/structure/types/manage/article/conditionals');
      $this->createScreenshot($this->screenshotPath . '03-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->assertSession()
        ->pageTextContains('body ' . $fieldName . ' visible value');

      // Visit Article Add form to check that conditions are applied.
      $this->drupalGet('node/add/article');

      // Check that the field Body is not visible.
      $this->createScreenshot($this->screenshotPath . '04-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '04.' . $fieldName . '. Article Body field is visible');

      // Change a select value set that should not show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $wrong_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is visible');

      // Change a select value set to show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $correct_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '06-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '06.' . $fieldName . '. Article Body field is not visible');

      // Change a select value set to hide the body again.
      $this->changeField($this->fieldSelectors[$fieldName], '_none');
      $this->createScreenshot($this->screenshotPath . '07-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '07.' . $fieldName . '. Article Body field is visible');

      // Return back to ConditionalFields configuration page for Article CT.
      $this->drupalGet('admin/structure/conditional_fields/node/article');

      // Delete previous condition.
      $this->click('li > button > .dropbutton-arrow');
      $this->clickLink('Delete');
      $this->submitForm([], 'Confirm');
      $this->createScreenshot($this->screenshotPath . '08-' . $fieldName . '_' . __FUNCTION__ . '.png');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();
    $allowed_values = [
      'select_single_entity_reference' => "1\n2",
      'select_single_list_integer' => "1\n2",
      'select_single_list_float' => "1.5\n2.5",
      'select_single_list_string' => "one\ntwo",
    ];
    $test_values = [
      'select_single_entity_reference' => 1,
      'select_single_list_integer' => 1,
      'select_single_list_float' => 1.5,
      'select_single_list_string' => 'one',
    ];

    // Visit a ConditionalFields configuration page for Content bundles.
    foreach ($this->fieldNames as $fieldName) {
      $this->createCondition('body', $fieldName, 'visible', 'value');

      $this->createScreenshot($this->screenshotPath . '01-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Set up conditions.
      $data = [
        'condition' => 'value',
        'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
        'values' => $allowed_values[$fieldName],
        'grouping' => 'AND',
        'state' => 'visible',
        'effect' => 'show',
      ];
      $this->submitForm($data, 'Save settings');
      $this->createScreenshot($this->screenshotPath . '02-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Check if that configuration is saved.
      $this->drupalGet('admin/structure/types/manage/article/conditionals');
      $this->createScreenshot($this->screenshotPath . '03-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->assertSession()
        ->pageTextContains('body ' . $fieldName . ' visible value');
      $this->clickLink('Edit');
      $this->createScreenshot($this->screenshotPath . '03.5-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Visit Article Add form to check that conditions are applied.
      $this->drupalGet('node/add/article');

      // Check that the field Body is not visible.
      $this->createScreenshot($this->screenshotPath . '04-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '04.' . $fieldName . '. Article Body field is visible');

      // Change a select value set that should not show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $test_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is visible');

      // Change a select value set to hide the body again.
      $this->changeField($this->fieldSelectors[$fieldName], '_none');
      $this->createScreenshot($this->screenshotPath . '07-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '07.' . $fieldName . '. Article Body field is visible');

      // Return back to ConditionalFields configuration page for Article CT.
      $this->drupalGet('admin/structure/conditional_fields/node/article');

      // Delete previous condition.
      $this->click('li > button > .dropbutton-arrow');
      $this->clickLink('Delete');
      $this->submitForm([], 'Confirm');
      $this->createScreenshot($this->screenshotPath . '08-' . $fieldName . '_' . __FUNCTION__ . '.png');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();
    $allowed_values = [
      'select_single_entity_reference' => "1\n2",
      'select_single_list_integer' => "1\n2",
      'select_single_list_float' => "1.5\n2.5",
      'select_single_list_string' => "one\ntwo",
    ];
    $correct_values = [
      'select_single_entity_reference' => 1,
      'select_single_list_integer' => 1,
      'select_single_list_float' => 1.5,
      'select_single_list_string' => 'one',
    ];
    $wrong_values = [
      'select_single_entity_reference' => 3,
      'select_single_list_integer' => 3,
      'select_single_list_float' => 3.5,
      'select_single_list_string' => 'three',
    ];

    // Visit a ConditionalFields configuration page for Content bundles.
    foreach ($this->fieldNames as $fieldName) {
      $this->createCondition('body', $fieldName, 'visible', 'value');

      $this->createScreenshot($this->screenshotPath . '01-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Set up conditions.
      $data = [
        'condition' => 'value',
        'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
        'values' => $allowed_values[$fieldName],
        'grouping' => 'AND',
        'state' => 'visible',
        'effect' => 'show',
      ];
      $this->submitForm($data, 'Save settings');
      $this->createScreenshot($this->screenshotPath . '02-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Check if that configuration is saved.
      $this->drupalGet('admin/structure/types/manage/article/conditionals');
      $this->createScreenshot($this->screenshotPath . '03-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->assertSession()
        ->pageTextContains('body ' . $fieldName . ' visible value');

      // Visit Article Add form to check that conditions are applied.
      $this->drupalGet('node/add/article');

      // Check that the field Body is not visible.
      $this->createScreenshot($this->screenshotPath . '04-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '04.' . $fieldName . '. Article Body field is visible');

      // Change a select value set that should be visible the body.
      $this->changeField($this->fieldSelectors[$fieldName], $correct_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is not visible');

      // Change a select value set that should not show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $wrong_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is visible');

      // Change a select value set to hide the body again.
      $this->changeField($this->fieldSelectors[$fieldName], '_none');
      $this->createScreenshot($this->screenshotPath . '07-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '07.' . $fieldName . '. Article Body field is visible');

      // Return back to ConditionalFields configuration page for Article CT.
      $this->drupalGet('admin/structure/conditional_fields/node/article');

      // Delete previous condition.
      $this->click('li > button > .dropbutton-arrow');
      $this->clickLink('Delete');
      $this->submitForm([], 'Confirm');
      $this->createScreenshot($this->screenshotPath . '08-' . $fieldName . '_' . __FUNCTION__ . '.png');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();
    $allowed_values = [
      'select_single_entity_reference' => "1\n2",
      'select_single_list_integer' => "1\n2",
      'select_single_list_float' => "1.5\n2.5",
      'select_single_list_string' => "one\ntwo",
    ];
    $correct_values = [
      'select_single_entity_reference' => 3,
      'select_single_list_integer' => 3,
      'select_single_list_float' => 3.5,
      'select_single_list_string' => 'three',
    ];
    $wrong_values = [
      'select_single_entity_reference' => 1,
      'select_single_list_integer' => 1,
      'select_single_list_float' => 1.5,
      'select_single_list_string' => 'one',
    ];

    // Visit a ConditionalFields configuration page for Content bundles.
    foreach ($this->fieldNames as $fieldName) {
      $this->createCondition('body', $fieldName, 'visible', 'value');

      $this->createScreenshot($this->screenshotPath . '01-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Set up conditions.
      $data = [
        'condition' => 'value',
        'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
        'values' => $allowed_values[$fieldName],
        'grouping' => 'AND',
        'state' => 'visible',
        'effect' => 'show',
      ];
      $this->submitForm($data, 'Save settings');
      $this->createScreenshot($this->screenshotPath . '02-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Check if that configuration is saved.
      $this->drupalGet('admin/structure/types/manage/article/conditionals');
      $this->createScreenshot($this->screenshotPath . '03-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->assertSession()
        ->pageTextContains('body ' . $fieldName . ' visible value');

      // Visit Article Add form to check that conditions are applied.
      $this->drupalGet('node/add/article');

      // Check that the field Body is not visible.
      $this->createScreenshot($this->screenshotPath . '04-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '04.' . $fieldName . '. Article Body field is visible');

      // Change a select value set that should be visible the body.
      $this->changeField($this->fieldSelectors[$fieldName], $correct_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is not visible');

      // Change a select value set that should not show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $wrong_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is visible');

      // Change a select value set to hide the body again.
      $this->changeField($this->fieldSelectors[$fieldName], '_none');
      $this->createScreenshot($this->screenshotPath . '07-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '07.' . $fieldName . '. Article Body field is visible');

      // Return back to ConditionalFields configuration page for Article CT.
      $this->drupalGet('admin/structure/conditional_fields/node/article');

      // Delete previous condition.
      $this->click('li > button > .dropbutton-arrow');
      $this->clickLink('Delete');
      $this->submitForm([], 'Confirm');
      $this->createScreenshot($this->screenshotPath . '08-' . $fieldName . '_' . __FUNCTION__ . '.png');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();
    $allowed_values = [
      'select_single_entity_reference' => "1\n2",
      'select_single_list_integer' => "1\n2",
      'select_single_list_float' => "1.5\n2.5",
      'select_single_list_string' => "one\ntwo",
    ];
    $correct_values = [
      'select_single_entity_reference' => 1,
      'select_single_list_integer' => 1,
      'select_single_list_float' => 1.5,
      'select_single_list_string' => 'one',
    ];

    // Visit a ConditionalFields configuration page for Content bundles.
    foreach ($this->fieldNames as $fieldName) {
      $this->createCondition('body', $fieldName, 'visible', 'value');

      $this->createScreenshot($this->screenshotPath . '01-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Set up conditions.
      $data = [
        'condition' => 'value',
        'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
        'values' => $allowed_values[$fieldName],
        'grouping' => 'AND',
        'state' => 'visible',
        'effect' => 'show',
      ];
      $this->submitForm($data, 'Save settings');
      $this->createScreenshot($this->screenshotPath . '02-' . $fieldName . '_' . __FUNCTION__ . '.png');

      // Check if that configuration is saved.
      $this->drupalGet('admin/structure/types/manage/article/conditionals');
      $this->createScreenshot($this->screenshotPath . '03-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->assertSession()
        ->pageTextContains('body ' . $fieldName . ' visible value');

      // Visit Article Add form to check that conditions are applied.
      $this->drupalGet('node/add/article');

      // Check that the field Body is not visible.
      $this->createScreenshot($this->screenshotPath . '04-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '04.' . $fieldName . '. Article Body field is visible');

      // Change a select value set that should not show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $correct_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '05.' . $fieldName . '. Article Body field is not visible');

      // Change a select value set that should be show the body.
      $this->changeField($this->fieldSelectors[$fieldName], $correct_values[$fieldName]);
      $this->createScreenshot($this->screenshotPath . '05-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilVisible('.field--name-body', 50, '06.' . $fieldName . '. Article Body field is not visible');

      // Change a select value set to hide the body again.
      $this->changeField($this->fieldSelectors[$fieldName], '_none');
      $this->createScreenshot($this->screenshotPath . '07-' . $fieldName . '_' . __FUNCTION__ . '.png');
      $this->waitUntilHidden('.field--name-body', 50, '07.' . $fieldName . '. Article Body field is visible');

      // Return back to ConditionalFields configuration page for Article CT.
      $this->drupalGet('admin/structure/conditional_fields/node/article');

      // Delete previous condition.
      $this->click('li > button > .dropbutton-arrow');
      $this->clickLink('Delete');
      $this->submitForm([], 'Confirm');
      $this->createScreenshot($this->screenshotPath . '08-' . $fieldName . '_' . __FUNCTION__ . '.png');
    }
  }

}
