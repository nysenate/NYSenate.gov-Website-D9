<?php

namespace Drupal\Tests\twig_field_value\Unit\FieldValue;

use Drupal\Tests\UnitTestCase;
use Drupal\twig_field_value\Twig\Extension\FieldValueExtension;

/**
 * @coversDefaultClass \Drupal\twig_field_value\Twig\Extension\FieldValueExtension
 * @group twig_field_value
 */
class FieldTargetEntityTest extends UnitTestCase {

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

    /** @var \Drupal\Core\Controller\ControllerResolverInterface $controllerResolver */
    $controllerResolver = $this->getMockBuilder('\Drupal\Core\Controller\ControllerResolver')
      ->disableOriginalConstructor()
      ->getMock();

    $loggerFactory = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannelFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->extension = new FieldValueExtension($controllerResolver, $loggerFactory);
  }

  /**
   * Returns a mock Content Entity object.
   *
   * @param array $referenced_entities
   *   The referenced entities.
   *
   * @return \Drupal\Core\Field\FieldItemBase
   *   The entity object.
   */
  protected function mockContentEntity(array $referenced_entities) {
    $entities = [];

    // Build the 'entity' objects with a property 'entity' that contains the
    // referenced entity.
    foreach ($referenced_entities as $referenced_entity) {
      $entity = new \stdClass();
      $entity->entity = $referenced_entity;
      $entities[] = $entity;
    }

    $field_item = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    $field_item->expects($this->any())
      ->method('get')
      ->will($this->returnValue($entities));

    return $field_item;
  }

  /**
   * Asserts the twig field_target_entity filter.
   *
   * @param mixed $expected_result
   *   The expected result.
   * @param mixed $render_array
   *   The render array.
   *
   * @dataProvider providerTestTargetEntity
   * @covers ::getTargetEntity
   */
  public function testTargetEntity($expected_result, $render_array) {

    $result = $this->extension->getTargetEntity($render_array);
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
  public function providerTestTargetEntity() {
    return [
      // Invalid render arrays.
      [NULL, NULL],
      [NULL, []],
      [
        NULL,
        ['#theme' => 'field', '#no_field_name' => []],
      ],
      [
        NULL,
        ['#theme' => 'field', '#field_name' => ['reference_field']],
      ],
    ];
  }

}
