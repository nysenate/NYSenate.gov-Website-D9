<?php

namespace Drupal\Tests\migmag_process\Unit\Plugin;

use Drupal\migmag_process\Plugin\migrate\process\MigMagCompare;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests the migmag_compare process plugin.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagCompare
 *
 * @group migmag_process
 */
class MigMagCompareTest extends MigrateTestCase {

  /**
   * Tests the transformation of the provided values.
   *
   * @param array $plugin_config
   *   The configuration of the tested plugin instance.
   * @param mixed $value
   *   The incoming value to test the transformation with.
   * @param mixed $expected_result
   *   The expected result of the transformation.
   * @param string|null $expected_exception_message
   *   The expected message of the MigrateException if the test case should end
   *   in a MigrateException. If this is NULL, then the test does not expects a
   *   MigrateException to be thrown. Defaults to NULL.
   *
   * @covers ::transform
   * @covers ::doCompare
   * @covers ::deliverReturnValue
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(array $plugin_config, $value, $expected_result, string $expected_exception_message = NULL): void {
    $migrate_executable = $this->prophesize(MigrateExecutableInterface::class);
    $row = $this->prophesize(Row::class);
    $plugin_config += ['plugin' => 'migmag_compare'];
    $plugin = new MigMagCompare(
      $plugin_config,
      $plugin_config['plugin'],
      []
    );

    if ($expected_exception_message) {
      $this->expectException(MigrateException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }
    $actual_result = $plugin->transform(
      $value,
      $migrate_executable->reveal(),
      $row->reveal(),
      'destination_property'
    );

    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestTransform(): array {
    $return_if_conf = [
      'true' => 'true',
      'false' => 'false',
      0 => 'equal',
      -1 => '1st less than 2nd',
      1 => '1st greater than 2nd',
    ];
    return [
      "No operator ('==='), expected FALSE" => [
        'config' => [],
        'value' => ['0', 0],
        'expected' => FALSE,
      ],
      "No operator ('==='), expected TRUE" => [
        'config' => [],
        'value' => ['foo', 'foo'],
        'expected' => TRUE,
      ],

      "'==' operator, expected FALSE" => [
        'config' => ['operator' => '=='],
        'value' => ['ab', 1],
        'expected' => FALSE,
      ],
      "'==' operator, expected TRUE" => [
        'config' => ['operator' => '=='],
        'value' => ['0', 0],
        'expected' => TRUE,
      ],
      "Why weak comparison ('==') is dangerous (with PHP < 8.0)" => [
        'config' => ['operator' => '=='],
        'value' => ['ab', 0],
        'expected' => PHP_MAJOR_VERSION < 8,
      ],

      "'===' operator, expected FALSE" => [
        'config' => ['operator' => '==='],
        'value' => ['1', 1],
        'expected' => FALSE,
      ],
      "'===' operator, expected TRUE" => [
        'config' => ['operator' => '==='],
        'value' => [1.234, 1.234],
        'expected' => TRUE,
      ],

      "'!=' operator, expected FALSE" => [
        'config' => ['operator' => '!='],
        'value' => ['0', 0],
        'expected' => FALSE,
      ],
      "'!=' operator, expected TRUE" => [
        'config' => ['operator' => '!='],
        'value' => ['ab', 1],
        'expected' => TRUE,
      ],
      "Why weak comparison ('!=') is dangerous (with PHP < 8.0)" => [
        'config' => ['operator' => '!='],
        'value' => ['ab', 0],
        'expected' => PHP_MAJOR_VERSION >= 8,
      ],
      "Mixed values #1, '<>' operator, expected FALSE" => [
        'config' => ['operator' => '<>'],
        'value' => ['1.2345', 1.2345],
        'expected' => FALSE,
      ],
      "Mixed values #2, '<>' operator" => [
        'config' => ['operator' => '<>'],
        'value' => ['0abc', 0],
        'expected' => PHP_MAJOR_VERSION >= 8,
      ],
      "Mixed values #3, '!=' operator, expected FALSE" => [
        'config' => ['operator' => '<>'],
        'value' => [NULL, ''],
        'expected' => FALSE,
      ],

      "'!==' operator, expected FALSE" => [
        'config' => ['operator' => '!=='],
        'value' => [1.2345, 1.2345],
        'expected' => FALSE,
      ],
      "'!==' operator, expected TRUE" => [
        'config' => ['operator' => '!=='],
        'value' => ['1.2345', 1.2345],
        'expected' => TRUE,
      ],

      "'<' operator #1, expected FALSE" => [
        'config' => ['operator' => '<'],
        'value' => [1.23456, 1.2345],
        'expected' => FALSE,
      ],

      "'<' operator #2, expected TRUE" => [
        'config' => ['operator' => '<'],
        'value' => [1.2345, 1.23456],
        'expected' => TRUE,
      ],
      "'<' operator #3, expected TRUE" => [
        'config' => ['operator' => '<'],
        'value' => ['1.2345', 1.23456],
        'expected' => TRUE,
      ],
      "'<' operator #4, expected FALSE" => [
        'config' => ['operator' => '<'],
        'value' => ['1.23456', 1.2345],
        'expected' => FALSE,
      ],

      "'<=' operator #1, expected FALSE" => [
        'config' => ['operator' => '<='],
        'value' => [1.2345, 1],
        'expected' => FALSE,
      ],
      "'<=' operator #2, expected TRUE" => [
        'config' => ['operator' => '<='],
        'value' => [1, 1.2345],
        'expected' => TRUE,
      ],
      "'<=' operator #3, expected TRUE" => [
        'config' => ['operator' => '<='],
        'value' => ['1.23456', 1.2345678],
        'expected' => TRUE,
      ],

      "'>' operator #1, expected FALSE" => [
        'config' => ['operator' => '>'],
        'value' => [1.23456, 1.2345678],
        'expected' => FALSE,
      ],
      "'>' operator #2, expected TRUE" => [
        'config' => ['operator' => '>'],
        'value' => [1.23456, 1.2345],
        'expected' => TRUE,
      ],

      "'>=' operator #1, expected FALSE" => [
        'config' => ['operator' => '>='],
        'value' => [1, 1.2345],
        'expected' => FALSE,
      ],
      "'>=' operator #2, expected TRUE" => [
        'config' => ['operator' => '>='],
        'value' => [1.2345, 1],
        'expected' => TRUE,
      ],
      "'>=' operator #3, expected TRUE" => [
        'config' => ['operator' => '>='],
        'value' => [1.2345678, '1.23456'],
        'expected' => TRUE,
      ],

      "Second object is always bigger" => [
        'config' => ['operator' => '>'],
        'value' => [(object) ['a' => 1], (object) []],
        'expected' => TRUE,
      ],

      "Second array is always bigger" => [
        'config' => ['operator' => '>'],
        'value' => [[1], []],
        'expected' => TRUE,
      ],

      "'<=>' operator #1" => [
        'config' => ['operator' => '<=>'],
        'value' => [1, 2],
        'expected' => -1,
      ],
      "'<=>' operator #2" => [
        'config' => ['operator' => '<=>'],
        'value' => [2, 2],
        'expected' => 0,
      ],
      "'<=>' operator #3" => [
        'config' => ['operator' => '<=>'],
        'value' => [3, 2],
        'expected' => 1,
      ],
      "'<=>' operator #4: object" => [
        'config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], (object) [1 => 0]],
        'expected' => 1,
      ],
      "'<=>' operator #5: object" => [
        'config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], (object) [1 => 1]],
        'expected' => 0,
      ],
      "'<=>' operator #6: object" => [
        'config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], (object) [1 => 2]],
        'expected' => -1,
      ],

      'return_if #1: true' => [
        'config' => ['return_if' => $return_if_conf],
        'value' => [1, 1],
        'expected' => 'true',
      ],
      'return_if #2: false' => [
        'config' => ['return_if' => $return_if_conf],
        'value' => [0, 1],
        'expected' => 'false',
      ],
      'return_if #3: less' => [
        'config' => [
          'operator' => '<=>',
          'return_if' => $return_if_conf,
        ],
        'value' => [0, 1],
        'expected' => '1st less than 2nd',
      ],
      'return_if #4: equal' => [
        'config' => [
          'operator' => '<=>',
          'return_if' => $return_if_conf,
        ],
        'value' => [1, 1],
        'expected' => 'equal',
      ],
      'return_if #5: greater' => [
        'config' => [
          'operator' => '<=>',
          'return_if' => $return_if_conf,
        ],
        'value' => [2, 1],
        'expected' => '1st greater than 2nd',
      ],

      "Exception: comparison fails" => [
        'config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], 1],
        'expected' => NULL,
        'exception' => "Comparison failed in 'migmag_compare' migrate process plugin with message: Object of class stdClass could not be converted to int.",
      ],
      "Exception: object value" => [
        'config' => [],
        'value' => (object) ['foo' => 'bar'],
        'expected' => NULL,
        'exception' => "'migmag_compare' migrate process plugin's processed value must be an array, got 'object'.",
      ],
      "Exception: integer value" => [
        'config' => [],
        'value' => 1,
        'expected' => NULL,
        'exception' => "'migmag_compare' migrate process plugin's processed value must be an array, got 'integer'.",
      ],
      "Exception: only one array value" => [
        'config' => [],
        'value' => [1],
        'expected' => NULL,
        'exception' => "'migmag_compare' migrate process plugin's processed array value must have at least two values.",
      ],
      "Exception: unsupported operator" => [
        'config' => ['operator' => 'foo'],
        'value' => [1, 2],
        'expected' => NULL,
        'exception' => "'migmag_compare' migrate process plugin does not support operator 'foo'.",
      ],
      "Exception: non-string operator" => [
        'config' => ['operator' => (object) ['foo']],
        'value' => [1, 2],
        'expected' => NULL,
        'exception' => "'migmag_compare' migrate process plugin's operator must be a string, got 'object'.",
      ],
    ];
  }

}
