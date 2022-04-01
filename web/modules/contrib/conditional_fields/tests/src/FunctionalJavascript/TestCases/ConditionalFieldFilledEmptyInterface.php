<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases;

/**
 * Outline tests for fields types that can be filled or empty.
 *
 * @package Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases
 */
interface ConditionalFieldFilledEmptyInterface {

  /**
   * The target field is Visible when the control field is Filled.
   */
  public function testVisibleFilled();

  /**
   * The target field is Visible when the control field is Empty.
   */
  public function testVisibleEmpty();

  /**
   * The target field is Invisible when the control field is Filled.
   */
  public function testInvisibleFilled();

  /**
   * The target field is Invisible when the control field is Empty.
   */
  public function testInvisibleEmpty();

}
