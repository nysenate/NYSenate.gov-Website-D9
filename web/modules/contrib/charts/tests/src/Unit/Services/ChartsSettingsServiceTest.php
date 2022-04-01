<?php

namespace Drupal\Tests\charts\Unit\Services;

use Drupal\Tests\UnitTestCase;
use Drupal\charts\Services\ChartsSettingsService;

/**
 * @coversDefaultClass \Drupal\charts\Services\ChartsSettingsService
 * @group charts
 */
class ChartsSettingsServiceTest extends UnitTestCase {

  /**
   * Tests if the default chart settings are available.
   *
   * @param array $config
   *   A default module configuration.
   *
   * @dataProvider chartsSettingsServiceProvider
   */
  public function testGetChartsSettings(array $config) {
    $chartsSettingsService = $this->createChartsSettingsService($config);
    $chartSettings = $chartsSettingsService->getChartsSettings();
    $this->assertArrayEquals($config, $chartSettings);
  }

  /**
   * Data provider for testGetChartsSettings().
   */
  public function chartsSettingsServiceProvider() {
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

  /**
   * Create a ChartsSettingsService object.
   *
   * @param array $config
   *   A default module configuration.
   *
   * @return \Drupal\charts\Services\ChartsSettingsService
   *   ChartsSettingsService object to be tested.
   */
  private function createChartsSettingsService(array $config) {
    return new ChartsSettingsService(
      $this->getConfigFactoryStub(
        [
          'charts.settings' => [
            'charts_default_settings' => $config,
          ],
        ]
      )
    );
  }

}
