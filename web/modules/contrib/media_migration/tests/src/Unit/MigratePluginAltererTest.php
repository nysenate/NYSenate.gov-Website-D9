<?php

namespace Drupal\Tests\media_migration\Unit;

use Drupal\media_migration\MigratePluginAlterer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests MigratePluginAlterer.
 *
 * @group media_migration
 *
 * @coversDefaultClass \Drupal\media_migration\MigratePluginAlterer
 */
class MigratePluginAltererTest extends UnitTestCase {

  /**
   * Tests getSourceValueOfMigrationProcess().
   *
   * @covers ::getSourceValueOfMigrationProcess
   *
   * @dataProvider getSourceValueOfMigrationProcessProvider
   */
  public function testGetSourceValueOfMigrationProcess(array $migration, string $process_property_key, $expected_return, $expected_exception): void {
    if (!empty($expected_exception)) {
      $this->expectException($expected_exception['class']);
      $this->expectExceptionMessage($expected_exception['message']);
    }
    $this->assertSame($expected_return, MigratePluginAlterer::getSourceValueOfMigrationProcess($migration, $process_property_key));
  }

  /**
   * Data provider for ::testGetSourceValueOfMigrationProcess.
   *
   * @return array[]
   *   The test cases.
   */
  public function getSourceValueOfMigrationProcessProvider(): array {
    $embedded_data_source = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'same' => 'same_val',
            'dynamic' => 'dynamic_one',
            'partial' => 'partial_val',
          ],
          [
            'same' => 'same_val',
            'dynamic' => 'dynamic_two',
          ],
        ],
        'constants' => [
          'foo' => 'foo_val',
        ],
      ],
    ];

    $migration = [
      'source' => [
        'plugin' => 'plugin_id',
        'foo' => 'foo_val',
        'bar' => 'bar_val',
        'foobar' => 'foobar_val',
        'constants' => [
          'foo' => [
            'bar' => [
              'baz' => 'foobarbaz_val',
            ],
          ],
        ],
      ],
      'process' => [
        'fooproc' => 'foo',
        'barproc' => [
          'plugin' => 'get',
          'source' => 'bar',
        ],
        'foobarproc' => [
          [
            'plugin' => 'get',
            'source' => 'foobar',
          ],
        ],
        'dynamic' => [
          [
            'plugin' => 'get',
            'source' => 'foobar',
          ],
          [
            'plugin' => 'static_map',
            'map' => [
              'mapthis' => 'tothis',
            ],
          ],
        ],
        'foobarbazproc' => 'constants/foo/bar/baz',
        'anotherproc' => 'constants/foo',
        'foo/bar/baz/proc' => 'foo',
        'missing_source' => 'missing_source_prop',
        'missing_from_constants' => 'constants/missing_prop',
        'embedsameproc' => [
          [
            'plugin' => 'get',
            'source' => 'same',
          ],
        ],
        'embeddynamicproc' => [
          'plugin' => 'get',
          'source' => 'dynamic',
        ],
        'embedpartialproc' => 'partial',
      ],
    ];

    return [
      'Property not available' => [
        'migration' => $migration,
        'property' => 'missing_process',
        'expected' => '',
        'exception' => [
          'class' => \LogicException::class,
          'message' => 'No corresponding process found',
        ],
      ],
      'Property process is a string' => [
        'migration' => $migration,
        'property' => 'fooproc',
        'expected' => 'foo_val',
        'exception' => NULL,
      ],
      'Property process is a plugin definition array' => [
        'migration' => $migration,
        'property' => 'barproc',
        'expected' => 'bar_val',
        'exception' => NULL,
      ],
      'Property process is an array of a single plugin definition array' => [
        'migration' => $migration,
        'property' => 'foobarproc',
        'expected' => 'foobar_val',
        'exception' => NULL,
      ],
      'Property process is an array of a multiple plugin definitions' => [
        'migration' => $migration,
        'property' => 'dynamic',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Property value is a multi-level constant defined with "Row::PROPERTY_SEPARATOR"' => [
        'migration' => $migration,
        'property' => 'foobarbazproc',
        'expected' => 'foobarbaz_val',
        'exception' => NULL,
      ],
      'Property value is a multi-level constant defined as array' => [
        'migration' => $migration,
        'property' => 'anotherproc',
        'expected' => [
          'bar' => [
            'baz' => 'foobarbaz_val',
          ],
        ],
        'exception' => NULL,
      ],
      'Property name contains "Row::PROPERTY_SEPARATOR"' => [
        'migration' => $migration,
        'property' => 'foo/bar/baz/proc',
        'expected' => 'foo_val',
        'exception' => NULL,
      ],
      'Property source is not available' => [
        'migration' => $migration,
        'property' => 'missing_source',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Property source is not available in a source property array' => [
        'migration' => $migration,
        'property' => 'missing_from_constants',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Embedded_data plugin, existing property value' => [
        'migration' => $embedded_data_source + $migration,
        'property' => 'embedsameproc',
        'expected' => 'same_val',
        'exception' => NULL,
      ],
      'Embedded_data plugin, existing property with dynamic value' => [
        'migration' => $embedded_data_source + $migration,
        'property' => 'embeddynamicproc',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Embedded_data plugin, existing property with partially avaliable value' => [
        'migration' => $embedded_data_source + $migration,
        'property' => 'embedpartialproc',
        'expected' => NULL,
        'exception' => NULL,
      ],
    ];
  }

}
