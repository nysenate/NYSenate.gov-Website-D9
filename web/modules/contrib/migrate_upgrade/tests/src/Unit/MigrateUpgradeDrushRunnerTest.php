<?php

namespace Drupal\Tests\migrate_upgrade\Unit;

use Drupal\migrate_upgrade\MigrateUpgradeDrushRunner;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the  MigrateUpgradeDrushRunner class.
 *
 * @group migrate_upgrade
 * @coversDefaultClass \Drupal\migrate_upgrade\MigrateUpgradeDrushRunner
 */
class MigrateUpgradeDrushRunnerTest extends MigrateTestCase {

  /**
   * Test the id substitution functions.
   *
   * @param array $source
   *   The source data.
   * @param array $expected
   *   The expected results.
   *
   * @covers ::substituteIds
   * @covers ::substituteMigrationIds
   *
   * @dataProvider getData
   */
  public function testIdSubstitution(array $source, array $expected): void {
    $loggerProphet = $this->prophesize(LoggerInterface::class);
    $runner = new TestMigrateUpgradeDrushRunner($loggerProphet->reveal());
    $results = $runner->substituteIds($source);
    $this->assertSame($expected, $results);
  }

  /**
   * Returns test data for the test.
   *
   * @return array
   *   The test data.
   */
  public function getData(): array {
    return [
      'Single Migration Lookup' => [
        'source_data' => [
          'id' => 'my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => 'my_previous_migration',
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'my_previous_migration',
              'required_dependency',
            ],
            'optional' => ['optional_dependency'],
          ],
        ],
        'expected_result' => [
          'id' => 'upgrade_my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => 'upgrade_my_previous_migration',
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'upgrade_my_previous_migration',
              'upgrade_required_dependency',
            ],
            'optional' => ['upgrade_optional_dependency'],
          ],
        ],
      ],
      'Dual Migration Lookup' => [
        'source_data' => [
          'id' => 'my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => [
                'my_previous_migration_1',
                'my_previous_migration_2',
              ],
              'source_ids' => [
                'my_previous_migration_1' => ['source_1'],
                'my_previous_migration_2' => ['source_2'],
              ],
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'my_previous_migration_1',
              'required_dependency',
            ],
            'optional' => [
              'my_previous_migration_2',
              'optional_dependency',
            ],
          ],
        ],
        'expected_result' => [
          'id' => 'upgrade_my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => [
                'upgrade_my_previous_migration_1',
                'upgrade_my_previous_migration_2',
              ],
              'source_ids' => [
                'upgrade_my_previous_migration_1' => ['source_1'],
                'upgrade_my_previous_migration_2' => ['source_2'],
              ],
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'upgrade_my_previous_migration_1',
              'upgrade_required_dependency',
            ],
            'optional' => [
              'upgrade_my_previous_migration_2',
              'upgrade_optional_dependency',
            ],
          ],
        ],
      ],
      'Derivative Migration Lookup' => [
        'source_data' => [
          'id' => 'my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => 'derivable_migration',
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'derivable_migration',
              'required_dependency',
            ],
            'optional' => ['optional_dependency'],
          ],
        ],
        'expected_result' => [
          'id' => 'upgrade_my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => [
                'upgrade_derivable_migration_derivitive_1',
                'upgrade_derivable_migration_derivitive_2',

              ],
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'upgrade_derivable_migration_derivitive_1',
              'upgrade_derivable_migration_derivitive_2',
              'upgrade_required_dependency',
            ],
            'optional' => ['upgrade_optional_dependency'],
          ],
        ],
      ],
    ];
  }

}

/**
 * Test class to expose protected methods.
 */
class TestMigrateUpgradeDrushRunner extends MigrateUpgradeDrushRunner {

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerInterface $logger, array $options = []) {
    parent::__construct($logger, $options);
    $this->migrationList = [
      'my_previous_migration' => [],
      'my_previous_migration_1' => [],
      'my_previous_migration_2' => [],
      'derivable_migration:derivitive_1' => [],
      'derivable_migration:derivitive_2' => [],
      'required_dependency' => [],
      'optional_dependency' => [],
    ];
  }

  // @codingStandardsIgnoreStart
  /**
   * {@inheritdoc}
   *
   */
  public function substituteIds(array $entity_array) {
    return parent::substituteIds($entity_array);
  }
  // @codingStandardsIgnoreEnd

}

namespace Drupal\migrate_upgrade;

if (!function_exists('drush_get_option')) {

  /**
   * Override for called function.
   *
   * @param mixed $option
   *   The name of the option to get.
   * @param mixed $default
   *   Optional. The value to return if the option has not been set.
   * @param mixed $context
   *   Optional. The context to check for the option. If this is set, only this
   *   context will be searched.
   *
   * @return mixed
   *   The default, for this override.
   */
  function drush_get_option($option, $default = NULL, $context = NULL) {
    return $default;
  }

}
