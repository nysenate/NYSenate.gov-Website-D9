<?php

namespace Drupal\Tests\smart_sql_idmap\Kernel;

use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\smart_sql_idmap\Plugin\migrate\id_map\SmartSql;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Verifies that destination IDs also have indexes.
 *
 * @group smart_sql_idmap
 */
class DestinationIdsIndexTest extends MigrateTestBase {

  /**
   * Migration definition of the test migration.
   */
  const TEST_MIGRATION = [
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
    'destination' => ['plugin' => 'mocked_destination_plugin'],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'smart_sql_idmap',
  ];

  /**
   * Migration instance used for testing.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The mocked migration destination instance.
   *
   * @var \Drupal\migrate\Plugin\MigrateDestinationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $destination;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $manager = $this->container->get('plugin.manager.migration');
    $this->migration = $manager->createStubMigration(static::TEST_MIGRATION);
    $this->destination = $this->getMockBuilder(MigrateDestinationInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $migration_reflection = new \ReflectionClass($this->migration);
    $migration_destination = $migration_reflection->getProperty('destinationPlugin');
    $migration_destination->setAccessible(TRUE);
    $migration_destination->setValue($this->migration, $this->destination);
  }

  /**
   * Verifies destination ID indexes are created.
   *
   * @param array $destination_ids
   *   Array of destination ID definitions.
   * @param array $expected_destination_indexes
   *   Names of the expected destination ID indexes.
   *
   * @dataProvider providerTestDestinationIdIndexes
   */
  public function testDestinationIdIndexes(array $destination_ids, array $expected_destination_indexes): void {
    $this->destination->method('getIds')
      ->willReturn($destination_ids);

    $id_map = $this->migration->getIdMap();
    $this->assertInstanceOf(SmartSql::class, $id_map);
    $id_map->rewind();

    $db_schema = \Drupal::database()->schema();
    $actual_indexes = [];
    foreach ($expected_destination_indexes as $index_name) {
      if (!$db_schema->indexExists($id_map->mapTableName(), $index_name)) {
        continue;
      }
      $actual_indexes[] = $index_name;
    }
    // From the three supported DBs in core, only MySQL has limitation on index
    // length.
    $expected_destination_indexes = \Drupal::database()->databaseType() === 'mysql'
      ? array_values($expected_destination_indexes)
      : [SmartSql::DESTINATION_INDEX];
    $this->assertSame($expected_destination_indexes, $actual_indexes);
    $key_count = count($expected_destination_indexes);
    // There shouldn't be more indexes with name starting with "destination"
    // than what we expect to exist.
    $this->assertFalse($db_schema->indexExists($id_map->mapTableName(), 'destination' . $key_count));
    // Source row status index must also exist.
    $this->assertTrue($db_schema->indexExists($id_map->mapTableName(), SmartSql::ROW_STATUS_INDEX));
  }

  /**
   * Data provider for ::testDestinationIdIndexes.
   *
   * @return array[][]
   *   The test cases.
   */
  public function providerTestDestinationIdIndexes(): array {
    return [
      'Single integer destination' => [
        'Dest IDs' => [
          'string_id' => ['type' => 'string'],
        ],
        'Expected MySQL destination index names' => [
          'destination',
        ],
      ],
      '14 int destination IDs, fit in one index' => [
        'Dest IDs' => [
          'int_col1' => ['type' => 'integer'],
          'int_col2' => ['type' => 'integer'],
          'int_col3' => ['type' => 'integer'],
          'int_col4' => ['type' => 'integer'],
          'int_col5' => ['type' => 'integer'],
          'int_col6' => ['type' => 'integer'],
          'int_col7' => ['type' => 'integer'],
          'int_col8' => ['type' => 'integer'],
          'int_col9' => ['type' => 'integer'],
          'int_col10' => ['type' => 'integer'],
          'int_col11' => ['type' => 'integer'],
          'int_col12' => ['type' => 'integer'],
          'int_col13' => ['type' => 'integer'],
          'int_col14' => ['type' => 'integer'],
        ],
        'Expected MySQL destination index names' => [
          'destination',
        ],
      ],
      'Multiple destination IDs' => [
        'Dest IDs' => [
          'string_col1' => ['type' => 'string'],
          'string_col2' => ['type' => 'string'],
          'string_col3' => ['type' => 'string'],
          'string_col4' => ['type' => 'string'],
          'string_col5' => ['type' => 'string'],
          'string_col6' => ['type' => 'string'],
          'string_col7' => ['type' => 'string'],
          'string_col8' => ['type' => 'string'],
          'string_col9' => ['type' => 'string'],
          'string_col10' => ['type' => 'string'],
          'string_col11' => ['type' => 'string'],
          'string_col12' => ['type' => 'string'],
          'string_col13' => ['type' => 'string'],
          'string_col14' => ['type' => 'string'],
        ],
        'Expected MySQL destination index names' => [
          'destination',
          'destination1',
          'destination2',
          'destination3',
        ],
      ],
      'Multiple destination IDs, first is integer' => [
        'Dest IDs' => [
          'integer_col1' => ['type' => 'integer'],
          'string_col2' => ['type' => 'string'],
          'string_col3' => ['type' => 'string'],
          'string_col4' => ['type' => 'string'],
          'string_col5' => ['type' => 'string'],
          'string_col6' => ['type' => 'string'],
          'string_col7' => ['type' => 'string'],
          'string_col8' => ['type' => 'string'],
          'string_col9' => ['type' => 'string'],
          'string_col10' => ['type' => 'string'],
          'string_col11' => ['type' => 'string'],
          'string_col12' => ['type' => 'string'],
          'string_col13' => ['type' => 'string'],
          'string_col14' => ['type' => 'string'],
        ],
        'Expected MySQL destination index names' => [
          'destination',
          'destination1',
          'destination2',
          'destination3',
        ],
      ],
    ];
  }

}
