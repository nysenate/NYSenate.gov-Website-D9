<?php

namespace Drupal\Tests\charts\Unit\Settings;

use Drupal\charts\Settings\ChartsDefaultColors;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ChartDefaultColors class.
 *
 * @coversDefaultClass \Drupal\charts\Settings\ChartsDefaultColors
 * @group charts
 */
class ChartsDefaultColorsTest extends UnitTestCase {

  /**
   * @var \Drupal\charts\Settings\ChartsDefaultColors
   */
  private $chartsDefaultColors;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->chartsDefaultColors = new ChartsDefaultColors();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->chartsDefaultColors = NULL;
  }

  /**
   * Tests the number of default colors.
   */
  public function testNumberOfDefaultColors() {
    $this->assertCount(10, $this->chartsDefaultColors->getDefaultColors());
  }

  /**
   * Tests getter and setter for default colors.
   *
   * @param array $color
   *   An array of color codes.
   *
   * @dataProvider colorProvider
   */
  public function testDefaultColors(array $color) {
    $this->chartsDefaultColors->setDefaultColors($color);
    $this->assertArrayEquals($color, $this->chartsDefaultColors->getDefaultColors());
  }

  /**
   * Data provider for setDefaultColors().
   */
  public function colorProvider() {
    yield [
      ['#2f7ed8'],
    ];
  }

}
