<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases;

/**
 * Outline tests for field types that can be both (in)visible and (un)checked.
 *
 * @package Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases
 */
interface ConditionalFieldCheckedUncheckedInterface {

  /**
   * The target field is Visible when the control field is Checked.
   */
  public function testVisibleChecked();

  /**
   * The target field is Visible when the control field is Unchecked.
   */
  public function testVisibleUnchecked();

  /**
   * The target field is Invisible when the control field is Unchecked.
   */
  public function testInvisibleUnchecked();

}
