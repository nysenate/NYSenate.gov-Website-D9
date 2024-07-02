<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases;

/**
 * Outline tests for field types that have visible values.
 *
 * @package Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases
 */
interface ConditionalFieldValueInterface {

  /**
   * The target field is Visible when the control field has value from Widget.
   */
  public function testVisibleValueWidget();

  /**
   * Target field is Visible when the control field has value from regex.
   */
  public function testVisibleValueRegExp();

  /**
   * Target field is Visible when control field has value with AND condition.
   */
  public function testVisibleValueAnd();

  /**
   * Target field is Visible when the control field has value with OR condition.
   */
  public function testVisibleValueOr();

  /**
   * Target field is Visible when control field has value with NOT condition.
   */
  public function testVisibleValueNot();

  /**
   * Target field is Visible when control field has value with XOR condition.
   */
  public function testVisibleValueXor();

}
