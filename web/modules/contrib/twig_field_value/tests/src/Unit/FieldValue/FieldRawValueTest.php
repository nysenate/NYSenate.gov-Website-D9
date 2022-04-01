<?php

namespace Drupal\Tests\twig_field_value\Unit\FieldValue;

use Drupal\Tests\UnitTestCase;
use Drupal\twig_field_value\Twig\Extension\FieldValueExtension;

/**
 * @coversDefaultClass \Drupal\twig_field_value\Twig\Extension\FieldValueExtension
 * @group twig_field_value
 */
class FieldRawValueTest extends UnitTestCase {

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
   * Returns a mock FieldItem.
   *
   * @param mixed $values
   *
   * @return \Drupal\Core\Field\FieldItemBase
   */
  protected function mockFieldItem($values) {

    $field_item  = $this->getMockBuilder('Drupal\Core\Field\FieldItemBase')
      ->disableOriginalConstructor()
      ->getMock();
    $field_item->expects($this->any())
      ->method('getValue')
      ->will($this->returnValue($values));

    return $field_item;
  }

  /**
   * Asserts the twig field_raw filter.
   *
   * @dataProvider providerTestRawValues
   * @covers ::getRawValues
   *
   * @param $expected_result
   * @param $render_array
   * @param $key
   */
  public function testRawValues($expected_result, $render_array, $key) {

    $result = $this->extension->getRawValues($render_array, $key);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerTestRawValues() {
    return [
      // Invalid render arrays.
      [NULL, NULL, ''],
      [NULL, [], ''],
      [
        NULL,
        ['#theme' => 'field', '#no_items' => []],
        '',
      ],
      [
        NULL,
        ['#theme' => 'field', '#items' => []],
        '',
      ],
      [
        NULL,
        ['#theme' => 'field', '#items' => $this->mockFieldItem(NULL)],
        '',
      ],
      // Request all values, field with single value.
      [
        ['value' => 'text_value'],
        [
          '#theme' => 'field',
          '#items' => $this->mockFieldItem([
           ['value' => 'text_value'],
          ]),
        ],
        '',
      ],
      // Request all values, field with multiple values.
      [[
        'alt' => 'alt_value',
        'title' => 'title_value',
      ],
        [
          '#theme' => 'field',
          '#items' => $this->mockFieldItem([
            [
              'alt' => 'alt_value',
              'title' => 'title_value',
            ],
          ]),
        ],
        '',
      ],
      // Request 'foo', but value not exist.
      [
        NULL,
        [
          '#theme' => 'field',
          '#items' => $this->mockFieldItem([
            [
              'alt' => 'alt_value',
              'title' => 'title_value',
            ],
          ]),
        ],
        'foo',
      ],
      // Request 'alt' value, field cardinality = 1.
      [
        'alt_value',
        [
          '#theme' => 'field',
          '#items' => $this->mockFieldItem([
            [
              'alt' => 'alt_value',
              'title' => 'title_value',
            ],
          ]),
        ],
        'alt',
      ],
      // Request all values, field cardinality = 2.
      [
        [
          [
            'alt' => 'alt_value_1',
            'title' => 'title_value_1',
          ],
          [
            'alt' => 'alt_value_2',
            'title' => 'title_value_2',
          ],
        ],
        [
          '#theme' => 'field',
          '#items' => $this->mockFieldItem([
            [
              'alt' => 'alt_value_1',
              'title' => 'title_value_1',
            ],
            [
              'alt' => 'alt_value_2',
              'title' => 'title_value_2',
            ],
          ]),
        ],
        '',
      ],
      // Request 'alt' value, field cardinality = 2.
      [
        ['alt_value_1', 'alt_value_2'],
        [
          '#theme' => 'field',
          '#items' => $this->mockFieldItem([
            [
              'alt' => 'alt_value_1',
              'title' => 'title_value_1',
            ],
            [
              'alt' => 'alt_value_2',
              'title' => 'title_value_2',
            ],
          ]),
        ],
        'alt',
      ],
    ];
  }

}
