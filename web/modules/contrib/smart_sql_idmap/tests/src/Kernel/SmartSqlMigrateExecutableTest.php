<?php

namespace Drupal\Tests\smart_sql_idmap\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\smart_sql_idmap\Plugin\migrate\id_map\SmartSql;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests Smart SQL ID map's compatibility with core's MigrateExecutable.
 *
 * @group smart_sql_idmap
 */
class SmartSqlMigrateExecutableTest extends MigrateTestBase {

  /**
   * The migration definition we test our sql plugin with.
   *
   * @const array
   */
  const MIGRATION = [
    'id' => 'test_migration',
    'idMap' => [
      'plugin' => 'smart_sql',
    ],
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [],
      'ids' => ['source_id' => ['type' => 'integer']],
    ],
    'process' => ['value' => 'destination_id'],
    'destination' => ['plugin' => 'dummy'],
  ];

  /**
   * {@inheritdoc}
   *
   * Access level should be public for Drupal core 8.9.x.
   */
  protected static $modules = [
    'migrate',
    'migrate_events_test',
    'smart_sql_idmap',
  ];

  /**
   * Tests Smart SQL ID map's compatibility with core's MigrateExecutable.
   *
   * @dataProvider providerTestSmartSqlMigrateExecutableCompatibility
   *
   * @see https://drupal.org/i/3227549
   * @see https://drupal.org/i/3227660
   */
  public function testSmartSqlMigrateExecutableCompatibility(array $source_records) {
    $manager = $this->container->get('plugin.manager.migration');
    assert($manager instanceof MigrationPluginManagerInterface);
    $definition = NestedArray::mergeDeepArray(
      [
        static::MIGRATION,
        ['source' => ['data_rows' => $source_records]],
      ],
      FALSE
    );
    $migration = $manager->createStubMigration($definition);

    // Populate the ID map plugin.
    $id_map = $migration->getIdMap();
    assert($id_map instanceof SmartSql);
    $executable = new MigrateExecutable($migration);
    $source = $migration->getSourcePlugin();
    $destination = $migration->getDestinationPlugin();
    $destination_ids = $destination->getIds();
    $source->rewind();
    foreach ($source as $row) {
      assert($row instanceof Row);
      $executable->processRow($row);
      $destination_values = $row->getDestination();
      $destination_id_values = array_reduce(array_keys($destination_ids), function (array $carry, string $key) use ($destination_values) {
        $carry[$key] = $destination_values[$key] ?? NULL;
        return $carry;
      }, []);
      $id_map->saveIdMapping($row, $destination_id_values, $row->getSourceProperty('source_row_status') ?? MigrateIdMapInterface::STATUS_IMPORTED, $row->getSourceProperty('rollback_action') ?? MigrateIdMapInterface::ROLLBACK_DELETE);
    }

    // Ensure that the test pre-filled the id map accordingly.
    $this->assertCount(count($source_records), $this->getRecordsOfSqlIdMap($id_map));

    $rollback_result = $executable->rollback();
    $this->assertEquals(MigrationInterface::RESULT_COMPLETED, $rollback_result);
    $this->assertCount(0, $this->getRecordsOfSqlIdMap($id_map));
  }

  /**
   * Data provider for ::testSmartSqlMigrateExecutableCompatibility.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestSmartSqlMigrateExecutableCompatibility() {
    return [
      'Rollback delete' => [
        'Source records' => [
          [
            'source_id' => '1',
            'destination_id' => '1',
          ],
        ],
      ],
      'Rollback preserve' => [
        'Source records' => [
          [
            'source_id' => '1',
            'destination_id' => '1',
            'rollback_action' => '1',
          ],
        ],
      ],
      'Rolling back a failed row' => [
        'Source records' => [
          [
            'source_id' => '1',
            'destination_id' => NULL,
            'source_row_status' => '2',
          ],
        ],
      ],
      'Rolling back with ID map having records with duplicated destination ID' => [
        'Source records' => [
          [
            'source_id' => '1',
            'destination_id' => '1',
          ],
          [
            'source_id' => '2',
            'destination_id' => '2',
          ],
          [
            'source_id' => '3',
            'destination_id' => '1',
          ],
        ],
      ],
    ];
  }

  /**
   * Returns the actual records found in a sql ID map table.
   *
   * @param \Drupal\migrate\Plugin\migrate\id_map\Sql $id_map
   *   A sql ID map plugin instance.
   *
   * @return string[][]
   *   The actual records found in the map table.
   */
  protected function getRecordsOfSqlIdMap(Sql $id_map) {
    $map_table_name = $id_map->mapTableName();
    return \Drupal::database()
      ->select($map_table_name, $map_table_name)
      ->fields($map_table_name)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

}
