<?php

namespace Drupal\Tests\migmag\Kernel;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests the MigMagNativeMigrateSqlTestBase base test class.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow
 * @group migmag
 */
class MigMagNativeMigrateSqlTestBaseTest extends MigMagNativeMigrateSqlTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerSource
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL, $expected_cache_key = NULL, $expected_failure_message = NULL) {
    if ($expected_failure_message) {
      $this->expectException(ExpectationFailedException::class);
      $this->expectExceptionMessage($expected_failure_message);
    }
    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water, $expected_cache_key);
  }

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $source_sql_data = [
      'variable' => [
        ['name' => 'foo', 'value' => 'i:1;'],
        ['name' => 'bar', 'value' => 'b:0;'],
      ],
    ];

    return [
      'Everything works as expected' => [
        'source' => $source_sql_data,
        'expected data' => [
          [
            'name' => 'foo',
            'value' => 1,
            'variables' => ['foo', 'bar'],
            'cache_counts' => TRUE,
          ],
          [
            'name' => 'bar',
            'value' => FALSE,
            'variables' => ['foo', 'bar'],
            'cache_counts' => TRUE,
          ],
        ],
        'count' => 2,
        'plugin configuration' => [
          'variables' => ['foo', 'bar'],
          'cache_counts' => TRUE,
        ],
        'highwater property' => NULL,
        'expected cache key' => 'variable_multirow-3ea288eb4deacf1ac8c36f0c5aa182b93892bdb7cd5c97e32460ee94ff885943',
      ],

      'Count mismatch - less rows' => [
        'source' => $source_sql_data,
        'expected data' => [
          ['name' => 'foo', 'value' => 1, 'variables' => ['foo', 'bar']],
        ],
        'count' => 2,
        'plugin configuration' => [
          'variables' => [
            'foo',
            'bar',
          ],
        ],
        'highwater property' => NULL,
        'expected cache key' => NULL,
        'expected assertion failure message' => 'Failed asserting that two arrays are equal.',
      ],

      'Count mismatch - wrong count' => [
        'source' => $source_sql_data,
        'expected data' => [
          ['name' => 'foo', 'value' => 1, 'variables' => ['foo', 'bar']],
        ],
        'count' => 2,
        'plugin configuration' => [
          'variables' => [
            'foo',
          ],
        ],
        'highwater property' => NULL,
        'expected cache key' => NULL,
        'expected assertion failure message' => 'Failed asserting that actual size 1 matches expected size 2.',
      ],

      'Cache key mismatch' => [
        'source' => $source_sql_data,
        'expected data' => [
          ['name' => 'foo', 'value' => 1, 'variables' => ['foo']],
        ],
        'count' => 1,
        'plugin configuration' => [
          'variables' => ['foo'],
          'cache_counts' => TRUE,
          'cache_key' => 'actual_cache_key',
        ],
        'highwater property' => NULL,
        'expected cache key' => 'something_else',
        'expected assertion failure message' => 'Failed asserting that two strings are identical.',
      ],
    ];
  }

}
