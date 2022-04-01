<?php

namespace Drupal\Tests\charts\Unit\Services;

use Drupal\Tests\UnitTestCase;
use Drupal\charts\Services\ChartService;

/**
 * @coversDefaultClass \Drupal\charts\Services\ChartService
 * @group charts
 */
class ChartServiceTest extends UnitTestCase {

  /**
   * @var \Drupal\charts\Services\ChartService
   */
  private $chartService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->chartService = new ChartService();
  }

  /**
   * Tests getter and setter for librarySelected.
   *
   * @param string $library
   *   The name of the library.
   *
   * @dataProvider libraryProvider
   */
  public function testlibrarySelected(string $library) {
    $this->chartService->setLibrarySelected($library);
    $this->assertEquals($library, $this->chartService->getLibrarySelected());
  }

  /**
   * Data provider for testLibrarySelected.
   */
  public function libraryProvider() {
    yield ['highcharts'];
    yield ['google'];
  }

}
