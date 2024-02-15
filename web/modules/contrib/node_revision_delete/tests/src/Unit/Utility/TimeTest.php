<?php

namespace Drupal\Tests\node_revision_delete\Unit\Utility;

use Drupal\node_revision_delete\Utility\Time;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Time class methods.
 *
 * @group node_revision_delete
 * @coversDefaultClass \Drupal\node_revision_delete\Utility\Time
 */
class TimeTest extends UnitTestCase {

  /**
   * Tests the convertWordToTime() method.
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param string $word
   *   The old word to map.
   *
   * @covers ::convertWordToTime
   * @dataProvider providerConvertWordToTime
   */
  public function testConvertWordToTime($expected, $word) {
    // Testing the function.
    $this->assertEquals($expected, Time::convertWordToTime($word));
  }

  /**
   * Data provider for testConvertWordToTime().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from Time::convertWordToTime().
   *   - 'word' - The old word to map.
   *
   * @see testConvertWordToTime()
   */
  public function providerConvertWordToTime() {
    $all_values = [
      'never'           => '-1',
      'every_time'      => '0',
      'every_hour'      => '3600',
      'everyday'        => '86400',
      'every_week'      => '604800',
      'every_10_days'   => '864000',
      'every_15_days'   => '1296000',
      'every_month'     => '2592000',
      'every_3_months'  => '7776000',
      'every_6_months'  => '15552000',
      'every_year'      => '31536000',
      'every_2_years'   => '63072000',
    ];

    $tests[] = [$all_values, NULL];
    $tests[] = [$all_values['never'], 'never'];
    $tests[] = [$all_values['every_time'], 'every_time'];
    $tests[] = [$all_values['every_hour'], 'every_hour'];
    $tests[] = [$all_values['everyday'], 'everyday'];
    $tests[] = [$all_values['every_week'], 'every_week'];
    $tests[] = [$all_values['every_10_days'], 'every_10_days'];
    $tests[] = [$all_values['every_15_days'], 'every_15_days'];
    $tests[] = [$all_values['every_month'], 'every_month'];
    $tests[] = [$all_values['every_3_months'], 'every_3_months'];
    $tests[] = [$all_values['every_6_months'], 'every_6_months'];
    $tests[] = [$all_values['every_year'], 'every_year'];
    $tests[] = [$all_values['every_2_years'], 'every_2_years'];

    return $tests;
  }

}
