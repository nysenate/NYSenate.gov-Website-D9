<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldCheckedUncheckedInterface;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields States.
 *
 * @group conditional_fields
 */
class ConditionalFieldRadiosTest extends ConditionalFieldTestBase implements
    ConditionalFieldValueInterface,
    ConditionalFieldCheckedUncheckedInterface {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/radios/';

  /**
   * The test's name to use in file names.
   *
   * @var string
   */
  protected $testName = 'Radios';

  /**
   * The name and vid of vocabulary, created for testing.
   *
   * @var string
   */
  protected $taxonomyName;

  /**
   * The amount of generated terms in created vocabulary.
   *
   * @var int
   */
  protected $termsCount;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a vocabulary with random name.
    $this->taxonomyName = $this->getRandomGenerator()->word(8);
    $vocabulary = Vocabulary::create([
      'name' => $this->taxonomyName,
      'vid' => $this->taxonomyName,
    ]);
    $vocabulary->save();
    // Create a random taxonomy terms for vocabulary.
    $this->termsCount = mt_rand(3, 5);
    for ($i = 1; $i <= $this->termsCount; $i++) {
      $termName = $this->getRandomGenerator()->word(8);
      Term::create([
        'parent' => [],
        'name' => $termName,
        'vid' => $this->taxonomyName,
      ])->save();
    }
    // Add a custom field with taxonomy terms to 'Article'.
    // The field label is a machine name of created vocabulary.
    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_' . $this->taxonomyName, $this->taxonomyName, 'taxonomy_term', 'default', $handler_settings);
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_' . $this->taxonomyName, ['type' => 'options_buttons'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET);
    // Random term id to check necessary value.
    $term_id = mt_rand(1, $this->termsCount);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id);
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();
    // Random term id to check necessary value.
    $term_id = mt_rand(1, $this->termsCount);
    do {
      $term_id_f = mt_rand(1, $this->termsCount);
    } while ($term_id_f == $term_id);
    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');
    // Set up conditions.
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => "^[{$term_id}]$",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id);
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_f, $term_id_f);
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');

    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_f);
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();
    $term_id = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id);
    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');
    $this->createScreenshot($this->screenshotPath . '01-' . $this->testName . __FUNCTION__ . '.png');
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => "{$term_id}\r\n{$term_id_2}",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');
    $this->createScreenshot($this->screenshotPath . '02-' . $this->testName . __FUNCTION__ . '.png');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '03-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
    $this->createScreenshot($this->screenshotPath . '04-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');

    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id);
    $this->createScreenshot($this->screenshotPath . '05-' . $this->testName . __FUNCTION__ . '.png');
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');

  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');
    // Random term id to check necessary value.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => "{$term_id_1}\r\n{$term_id_2}",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1);
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2);
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');
    // Change a condition's values set and the value.
    // Random term id to check necessary value.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => "{$term_id_1}\r\n{$term_id_2}",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilVisible('.field--name-body', 0, '01. Article Body field is not visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 50, '02. Article Body field is visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1);
    $this->waitUntilVisible('.field--name-body', 50, '03. Article Body field is not visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 50, '04. Article Body field is visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2);
    $this->waitUntilVisible('.field--name-body', 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');
    // Change a condition's values set and the value.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => "{$term_id_1}\r\n{$term_id_2}",
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 50, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1);
    $this->waitUntilHidden('.field--name-body', 50, '03. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2);
    $this->waitUntilHidden('.field--name-body', 50, '05. Article Body field is visible');
    // Change a select value set to hide the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 50, '04. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleChecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'checked');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, 'Article Body field is visible');
    for ($term_id = 1; $term_id < $this->termsCount; $term_id++) {
      // Change a select value set to show the body.
      $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
      $this->waitUntilVisible('.field--name-body', 50, $term_id . '. Article Body field is not visible');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleUnchecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', '!checked');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible !checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->waitUntilVisible('.field--name-body', 50, 'Article Body field is not visible');
    for ($term_id = 1; $term_id < $this->termsCount; $term_id++) {
      // Change a select value set to hide the body.
      $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
      $this->waitUntilHidden('.field--name-body', 50, $term_id . '. Article Body field is visible');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleUnchecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'field_' . $this->taxonomyName, '!visible', '!checked');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' !visible !checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is visible.
    $this->waitUntilHidden('.field--name-body', 50, 'Article Body field is visible');
    for ($term_id = 1; $term_id < $this->termsCount; $term_id++) {
      // Change a select value set to hide the body.
      $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id, $term_id);
      $this->waitUntilVisible('.field--name-body', 50, $term_id . '. Article Body field is not visible');
    }
  }

}
