<?php

namespace Drupal\Tests\twig_field_value\Unit\FieldValue;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
  protected function setUp(): void {

    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $entityRepository = $this->createMock(EntityRepositoryInterface::class);
    $controllerResolver = $this->createMock(ControllerResolverInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->extension = new FieldValueExtension($languageManager, $entityRepository, $controllerResolver, $loggerFactory);
  }

  /**
   * Returns a mock FieldItem.
   *
   * @param mixed $values
   *   The values.
   *
   * @return \Drupal\Core\Field\FieldItemBase
   *   The entity object.
   */
  protected function mockFieldItem($values) {
    $field_item = $this->createMock('Drupal\Core\Field\FieldItemBase');
    $field_item->expects($this->any())
      ->method('getValue')
      ->will($this->returnValue($values));

    return $field_item;
  }

  /**
   * Asserts the twig field_raw filter.
   *
   * @param mixed $expected_result
   *   The expected result.
   * @param mixed $render_array
   *   The render array.
   * @param string $key
   *   The key.
   *
   * @dataProvider providerTestRawValues
   * @covers ::getRawValues
   * function put.
   */
  public function testRawValues($expected_result, $render_array, $key) {

    $result = $this->extension->getRawValues($render_array, $key);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * This only tests invalid render arrays formats. Valid render arrays are
   * covered by functional tests.
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
    ];
  }

}
