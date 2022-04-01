<?php

namespace Drupal\Tests\charts\Unit\Settings;

use Drupal\charts\Settings\ChartsDefaultSettings;
use Drupal\Tests\UnitTestCase;
use Drupal\charts\Settings\ChartsDefaultColors;

/**
 * @coversDefaultClass \Drupal\charts\Settings\ChartsDefaultSettings
 * @group charts
 */
class ChartsDefaultSettingsTest extends UnitTestCase {

  /**
   * @var \Drupal\charts\Settings\ChartsDefaultSettings
   */
  private $chartsDefaultSettings;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $chartsDefaultColorsMock = $this->getDefaultColorsMock();
    $this->chartsDefaultSettings = new ChartsDefaultSettings();
    $colorsProperty = new \ReflectionProperty(ChartsDefaultSettings::class, 'colors');
    $colorsProperty->setAccessible(TRUE);
    $colorsProperty->setValue($this->chartsDefaultSettings, $chartsDefaultColorsMock);
  }

  /**
   * Get a default colors mock.
   */
  private function getDefaultColorsMock() {
    $chartsDefaultColors = $this->prophesize(ChartsDefaultColors::class);
    $chartsDefaultColors->getDefaultColors()->willReturn(['#2f7ed8']);
    return $chartsDefaultColors->reveal();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->chartsDefaultSettings = NULL;
  }

  /**
   * Tests the numver of defaults settings.
   */
  public function testNumberOfDefaultSettings() {
    $this->assertCount(39, $this->chartsDefaultSettings->getDefaults());
  }

  /**
   * Tests getter and setter for defaults.
   *
   * @param array $defaults
   *   Array of default settings.
   *
   * @dataProvider defaultSettingsProvider
   */
  public function testDefaults(array $defaults) {
    $this->chartsDefaultSettings->setDefaults($defaults);
    $this->assertArrayEquals($defaults, $this->chartsDefaultSettings->getDefaults());
  }

  /**
   * Data provider for setDefaults.
   */
  public function defaultSettingsProvider() {
    yield
    [
      [
        'width' => 400,
        'width_units' => 'px',
        'height' => 300,
        'height_units' => 'px',
        'colors' => ['#2f7ed8'],
      ],
    ];
  }

}
