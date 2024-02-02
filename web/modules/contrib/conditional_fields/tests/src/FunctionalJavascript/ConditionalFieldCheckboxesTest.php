<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;

/**
 * Test Conditional Fields Checkboxes Plugin.
 *
 * @group conditional_fields
 */
class ConditionalFieldCheckboxesTest extends ConditionalFieldTestBase implements ConditionalFieldValueInterface {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/checkboxes/';

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
    $this->termsCount = mt_rand(3, 6);
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
    $this->createEntityReferenceField('node', 'article', 'field_' . $this->taxonomyName, $this->taxonomyName, 'taxonomy_term', 'default', $handler_settings, -1);
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_' . $this->taxonomyName, ['type' => 'options_buttons'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');
    $this->clickLink('Edit');
    $this->createScreenshot($this->screenshotPath . '01. Checkboxes' . __FUNCTION__ . '.jpg');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->createScreenshot($this->screenshotPath . '02. Checkboxes' . __FUNCTION__ . '.jpg');
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '02. Article Body field is visible');

    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 60, '03. Article Body field is not visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, '04. Article Body field is visible');

  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $this->changeSelect('#edit-regex', "{$term_id_1}");

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 60, '02. Article Body field is not visible');

    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '03. Article Body field is visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 60, '04. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $values = $term_id_1 . '\r\n' . $term_id_2;
    $this->changeField('#edit-values', $values);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->createScreenshot($this->screenshotPath . 'scr1BodyVisCheckboxes.jpg');
    $this->waitUntilVisible('.field--name-body', 60, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, '03. Article Body field is visible');
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '04. Article Body field is visible');
    $this->createScreenshot($this->screenshotPath . 'scr2BodyHidCheckboxes.jpg');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $values = $term_id_1 . '\r\n' . $term_id_2;
    $this->changeField('#edit-values', $values);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 60, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, '03. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 60, '04. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $values = $term_id_1 . '\r\n' . $term_id_2;
    $this->changeField('#edit-values', $values);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilVisible('.field--name-body', 0, '01. Article Body field is not visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, '02. Article Body field is visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 60, '03. Article Body field is not visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '04. Article Body field is visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 60, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition('body', 'field_' . $this->taxonomyName, 'visible', 'value');

    // Change a condition's values set and the value.
    $this->changeField('#edit-values-set', ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR);
    // Random term id to check necessary values.
    $term_id_1 = mt_rand(1, $this->termsCount);
    do {
      $term_id_2 = mt_rand(1, $this->termsCount);
    } while ($term_id_2 == $term_id_1);
    $values = $term_id_1 . '\r\n' . $term_id_2;
    $this->changeField('#edit-values', $values);

    // Submit the form.
    $this->getSession()
      ->executeScript("jQuery('#conditional-field-edit-form').submit();");

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/types/manage/article/conditionals');
    $this->assertSession()
      ->pageTextContains('body field_' . $this->taxonomyName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden('.field--name-body', 0, '01. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilVisible('.field--name-body', 60, '02. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->waitUntilHidden('.field--name-body', 60, '03. Article Body field is visible');
    // Change a select value set to show the body.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilVisible('.field--name-body', 60, '04. Article Body field is not visible');
    // Change a select value set to hide the body again.
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '05. Article Body field is  visible');

    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_1, $term_id_1);
    $this->changeSelect('#edit-field-' . $this->taxonomyName . '-' . $term_id_2, $term_id_2);
    $this->waitUntilHidden('.field--name-body', 60, '05. Article Body field is  visible');
  }

}
