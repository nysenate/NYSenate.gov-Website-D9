<?php

namespace Drupal\Tests\charts\Unit\Settings;

use Drupal\charts\Settings\ChartsTypeInfo;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Tests the ChartsTypeInfo class.
 *
 * @coversDefaultClass \Drupal\charts\Settings\ChartsTypeInfo
 * @group charts
 */
class ChartsTypeInfoTest extends UnitTestCase {

  /**
   * @var \Drupal\charts\Settings\ChartsTypeInfo
   */
  private $chartsTypeInfo;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->chartsTypeInfo = new ChartsTypeInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->chartsTypeInfo = NULL;
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Data provider for testChartsChartsTypeInfo(), testGetChartTypes and testGetChartType().
   */
  public function chartsChartsTypeInfo() {
    yield ['area'];
    yield ['bar'];
    yield ['column'];
    yield ['donut'];
    yield ['gauge'];
    yield ['line'];
    yield ['pie'];
    yield ['scatter'];
    yield ['spline'];
  }

  /**
   * Tests chartsChartsTypeInfo().
   *
   * @param string $chartType
   *   The chart type.
   *
   * @dataProvider chartsChartsTypeInfo
   */
  public function testChartsChartsTypeInfo(string $chartType) {
    $chartsTypeInfo = $this->chartsTypeInfo->chartsChartsTypeInfo();
    $this->assertArrayHasKey($chartType, $chartsTypeInfo);
    $this->assertArrayHasKey('label', $chartsTypeInfo[$chartType]);
    $this->assertArrayHasKey('axis', $chartsTypeInfo[$chartType]);
  }

  /**
   * Tests getChartTypes().
   *
   * @param string $chartType
   *   The chart type.
   *
   * @dataProvider chartsChartsTypeInfo
   */
  public function testGetChartTypes(string $chartType) {
    $chartTypes = $this->chartsTypeInfo->getChartTypes();
    $this->assertArrayHasKey($chartType, $chartTypes);
    $this->assertIsString($chartTypes[$chartType]->render());
  }

  /**
   * Tests getChartType().
   *
   * @param string $chartType
   *   The chart type.
   *
   * @dataProvider chartsChartsTypeInfo
   */
  public function testGetChartType(string $chartType) {
    $chartTypeInfo = $this->chartsTypeInfo->getChartType($chartType);
    $this->assertIsArray($chartTypeInfo);
    $this->assertArrayHasKey('label', $chartTypeInfo);
    $this->assertArrayHasKey('axis', $chartTypeInfo);
  }

  /**
   * Tests getChartType() with a nonexistent chart type.
   */
  public function testGetChartTypeWithNonExistentChartType() {
    $chartTypeInfo = $this->chartsTypeInfo->getChartType('some_type');
    $this->assertFalse($chartTypeInfo);
  }

}
