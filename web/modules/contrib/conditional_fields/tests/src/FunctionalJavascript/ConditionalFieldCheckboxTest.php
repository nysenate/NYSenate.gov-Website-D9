<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldCheckedUncheckedInterface;

/**
 * Test Conditional Fields Checkbox state.
 *
 * @group conditional_fields
 */
class ConditionalFieldCheckboxTest extends ConditionalFieldTestBase implements ConditionalFieldCheckedUncheckedInterface {

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/checkbox/';

  /**
   * {@inheritdoc}
   */
  public function testVisibleChecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'promote', 'visible', 'checked');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body promote visible checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilVisible('.field--name-body', 10, '01. Article Body field is not visible');
    $this->changeSelect('#edit-promote-value', FALSE);
    $this->waitUntilHidden('.field--name-body', 0, '02. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleUnchecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'promote', 'visible', '!checked');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body promote visible !checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden('.field--name-body', 10, '01. Article Body field is visible');
    $this->changeSelect('#edit-promote-value', FALSE);
    $this->waitUntilVisible('.field--name-body', 0, '02. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleChecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'promote', '!visible', 'checked');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body promote !visible checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden('.field--name-body', 10, '01. Article Body field is visible');
    $this->changeSelect('#edit-promote-value', FALSE);
    $this->waitUntilVisible('.field--name-body', 0, '02. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleUnchecked() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition('body', 'promote', '!visible', '!checked');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/conditional_fields/node/article');
    $this->assertSession()->pageTextContains('body promote !visible !checked');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilVisible('.field--name-body', 10, '01. Article Body field is not visible');
    $this->changeSelect('#edit-promote-value', FALSE);
    $this->waitUntilHidden('.field--name-body', 0, '02. Article Body field is visible');
  }

}
