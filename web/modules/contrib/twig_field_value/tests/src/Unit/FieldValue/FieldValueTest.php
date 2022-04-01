<?php

namespace Drupal\Tests\twig_field_value\Unit\FieldValue;

use Drupal\Tests\UnitTestCase;
use Drupal\twig_field_value\Twig\Extension\FieldValueExtension;

/**
 * @coversDefaultClass \Drupal\twig_field_value\Twig\Extension\FieldValueExtension
 * @group twig_field_value
 */
class FieldValueTest extends UnitTestCase {

  /**
   * The Twig extension under test.
   *
   * @var \Drupal\twig_field_value\Twig\Extension\FieldValueExtension
   */
  protected $extension;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->extension = new FieldValueExtension();
  }

  /**
   * Asserts the twig field_label filter.
   *
   * @dataProvider providerTestFieldLabel
   * @covers ::getFieldLabel
   *
   * @param $expected_result
   * @param $render_array
   */
  public function testFieldLabel($expected_result, $render_array) {
    $result = $this->extension->getFieldLabel($render_array);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerTestFieldLabel() {
    return [
      [NULL, []],
      [NULL, ['#title' => 'foo']],
      [NULL, ['#theme' => 'item_list']],
      [NULL, ['#theme' => 'field']],
      ['My title', ['#theme' => 'field', '#title' => 'My title']],
    ];
  }

  /**
   * Asserts the twig field_value filter.
   *
   * @dataProvider providerTestFieldValue
   * @covers ::getFieldValue
   *
   * @param $expected_result
   * @param $render_array
   */
  public function testFieldValue($expected_result, $render_array) {
    $result = $this->extension->getFieldValue($render_array);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerTestFieldValue() {
    return [
      [NULL, NULL],
      [NULL, []],
      [NULL, ['#items' => 'foo']],
      [NULL, ['#theme' => 'item_list']],
      [NULL, ['#theme' => 'field']],
      [
        [
          0 => ['#markup' => 'this value'],
        ],
        [
          '#theme' => 'field',
          '#items' => [
            0 => 'dummy',
          ],
          0 => [
            '#markup' => 'this value',
          ],
        ],
      ],
      [
        [
          0 => ['#markup' => 'zero'],
          2 => ['#markup' => 'two'],
          3 => ['#markup' => 'three'],
        ],
        [
          '#theme' => 'field',
          '#items' => [
            0 => 'dummy',
            1 => 'dummy',
            2 => 'dummy',
            3 => 'dummy',
          ],
          0 => ['#markup' => 'zero'],
          2 => ['#markup' => 'two'],
          3 => ['#markup' => 'three'],
        ],
      ],
    ];
  }

}
