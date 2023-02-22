<?php

namespace Drupal\Tests\smart_sql_idmap\Unit\Plugin\migrate\id_map;

use Drupal\Component\Plugin\PluginBase;
use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateSqlIdMapTest;
use Drupal\Tests\smart_sql_idmap\Unit\TestSmartSqlIdMap;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests the Smart SQL ID map plugin.
 *
 * @group smart_sql_idmap
 */
class SmartSqlTest extends MigrateSqlIdMapTest {

  /**
   * {@inheritdoc}
   */
  protected $migrationConfiguration = [
    'id' => 'smart_sql_idmap_test',
  ];

  /**
   * The expected map table name.
   *
   * @var string
   */
  protected $expectedMapTableName = 'm_map_smart_sql_idmap_test';

  /**
   * The expected map table name with "prefix" as prefix.
   *
   * @var string
   */
  protected $expectedPrefixedMapTableName = 'm_map_smart_sql_idmap_test';

  /**
   * The expected message table name.
   *
   * @var string
   */
  protected $expectedMessageTableName = 'm_message_smart_sql_idmap_test';

  /**
   * Saves a single ID mapping row in the database.
   *
   * @param array $map
   *   The row to save.
   */
  protected function saveMap(array $map) {
    $table = $this->getIdMap()->mapTableName();
    $schema = $this->database->schema();
    // If the table already exists, add any columns which are in the map array,
    // but don't yet exist in the table. Yay, flexibility!
    if ($schema->tableExists($table)) {
      foreach (array_keys($map) as $field) {
        if (!$schema->fieldExists($table, $field)) {
          $schema->addField($table, $field, ['type' => 'text']);
        }
      }
    }
    else {
      $schema->createTable($table, $this->createSchemaFromRow($map));
    }

    $this->database->insert($table)->fields($map)->execute();
  }

  /**
   * Creates a test SQL ID map plugin.
   *
   * @return \Drupal\Tests\smart_sql_idmap\Unit\TestSmartSqlIdMap
   *   A SQL ID map plugin test instance.
   */
  protected function getIdMap() {
    $migration = $this->getMigration();

    $plugin = $this->createMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $plugin
      ->method('getIds')
      ->willReturn($this->sourceIds);
    $migration
      ->method('getSourcePlugin')
      ->willReturn($plugin);

    $plugin = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $plugin
      ->method('getIds')
      ->willReturn($this->destinationIds);
    $migration
      ->method('getDestinationPlugin')
      ->willReturn($plugin);
    $event_dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $migration_plugin_manager = $this->createMock(MigrationPluginManagerInterface::class);
    $base_plugin_id = substr($migration->id(), 0, strpos($migration->id(), PluginBase::DERIVATIVE_SEPARATOR));

    if ($base_plugin_id) {
      $migration_plugin_manager
        ->expects($this->any())
        ->method('getDefinitions')
        ->willReturn([]);
    }

    $id_map = new TestSmartSqlIdMap($this->database, $migration_plugin_manager, [], 'smart_sql', [], $migration, $event_dispatcher);
    $migration
      ->method('getIdMap')
      ->willReturn($id_map);

    return $id_map;
  }

  /**
   * Tests the ID mapping method.
   *
   * Create two ID mappings and update the second to verify that:
   * - saving new to empty tables work.
   * - saving new to nonempty tables work.
   * - updating work.
   */
  public function testSaveIdMapping() {
    $source = [
      'source_id_property' => 'source_value',
    ];
    $row = new Row($source, ['source_id_property' => []]);
    $id_map = $this->getIdMap();
    $id_map->saveIdMapping($row, ['destination_id_property' => 2]);
    $expected_result = [
      [
        'sourceid1' => 'source_value',
        'source_ids_hash' => $this->getIdMap()->getSourceIdsHash($source),
        'destid1' => 2,
      ] + $this->idMapDefaults(),
    ];
    $this->queryResultTest($this->getIdMapContents(), $expected_result);
    $source = [
      'source_id_property' => 'source_value_1',
    ];
    $row = new Row($source, ['source_id_property' => []]);
    $id_map->saveIdMapping($row, ['destination_id_property' => 3]);
    $expected_result[] = [
      'sourceid1' => 'source_value_1',
      'source_ids_hash' => $this->getIdMap()->getSourceIdsHash($source),
      'destid1' => 3,
    ] + $this->idMapDefaults();
    $this->queryResultTest($this->getIdMapContents(), $expected_result);
    $id_map->saveIdMapping($row, ['destination_id_property' => 4]);
    $expected_result[1]['destid1'] = 4;
    $this->queryResultTest($this->getIdMapContents(), $expected_result);
  }

  /**
   * Tests the getRowsNeedingUpdate method for rows that need an update.
   */
  public function testGetRowsNeedingUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = [
        'sourceid1' => 'source_value_' . $status,
        'source_ids_hash' => $this->getIdMap()->getSourceIdsHash($source),
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      ];
      // Assert zero rows need an update.
      if ($status == MigrateIdMapInterface::STATUS_IMPORTED) {
        $rows_needing_update = $id_map->getRowsNeedingUpdate(1);
        $this->assertCount(0, $rows_needing_update);
      }
    }
    // Assert that test values exist.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);

    // Assert a single row needs an update.
    $row_needing_update = $id_map->getRowsNeedingUpdate(1);
    $this->assertCount(1, $row_needing_update);

    // Assert the row matches its original source.
    $source_id = $expected_results[MigrateIdMapInterface::STATUS_NEEDS_UPDATE]['sourceid1'];
    $test_row = $id_map->getRowBySource(['source_id_property' => $source_id]);
    // $row_needing_update is an array of objects returned from the database,
    // but $test_row is an array, so the cast is necessary.
    $this->assertSame($test_row, (array) $row_needing_update[0]);

    // Add additional row that needs an update.
    $source = ['source_id_property' => 'source_value_multiple'];
    $row = new Row($source, ['source_id_property' => []]);
    $destination = ['destination_id_property' => 'destination_value_multiple'];
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);

    // Assert multiple rows need an update.
    $rows_needing_update = $id_map->getRowsNeedingUpdate(2);
    $this->assertCount(2, $rows_needing_update);
  }

  /**
   * Tests lookupDestinationIds().
   */
  public function testLookupDestinationIds() {
    // Simple map with one source and one destination ID.
    $id_map = $this->setupRows(['nid'], ['nid'], [
      [1, 101],
      [2, 102],
      [3, 103],
    ]);

    // Lookup nothing, gives nothing.
    $this->assertEquals([], $id_map->lookupDestinationIds([]));
    // Lookup by complete non-associative list.
    $this->assertEquals([[101]], $id_map->lookupDestinationIds([1]));
    $this->assertEquals([[102]], $id_map->lookupDestinationIds([2]));
    $this->assertEquals([], $id_map->lookupDestinationIds([99]));
    // Lookup by complete associative list.
    $this->assertEquals([[101]], $id_map->lookupDestinationIds(['nid' => 1]));
    $this->assertEquals([[102]], $id_map->lookupDestinationIds(['nid' => 2]));
    $this->assertEquals([], $id_map->lookupDestinationIds(['nid' => 99]));

    // Map with multiple source and destination IDs.
    $id_map = $this->setupRows(['nid', 'language'], ['nid', 'langcode'], [
      [1, 'en', 101, 'en'],
      [1, 'fr', 101, 'fr'],
      [1, 'de', 101, 'de'],
      [2, 'en', 102, 'en'],
    ]);

    // Lookup nothing, gives nothing.
    $this->assertEquals([], $id_map->lookupDestinationIds([]));
    // Lookup by complete non-associative list.
    $this->assertEquals([
      [101, 'en'],
    ], $id_map->lookupDestinationIds([1, 'en']));
    $this->assertEquals([
      [101, 'fr'],
    ], $id_map->lookupDestinationIds([1, 'fr']));
    $this->assertEquals([
      [102, 'en'],
    ], $id_map->lookupDestinationIds([2, 'en']));
    $this->assertEquals([], $id_map->lookupDestinationIds([2, 'fr']));
    $this->assertEquals([], $id_map->lookupDestinationIds([99, 'en']));
    // Lookup by complete associative list.
    $this->assertEquals([
      [101, 'en'],
    ], $id_map->lookupDestinationIds(['nid' => 1, 'language' => 'en']));
    $this->assertEquals([
      [101, 'fr'],
    ], $id_map->lookupDestinationIds(['nid' => 1, 'language' => 'fr']));
    $this->assertEquals([
      [102, 'en'],
    ], $id_map->lookupDestinationIds(['nid' => 2, 'language' => 'en']));
    $this->assertEquals([], $id_map->lookupDestinationIds([
      'nid' => 2,
      'language' => 'fr',
    ]));
    $this->assertEquals([], $id_map->lookupDestinationIds([
      'nid' => 99,
      'language' => 'en',
    ]));
    // Lookup by partial non-associative list.
    $this->assertEquals([
      [101, 'en'],
      [101, 'fr'],
      [101, 'de'],
    ], $id_map->lookupDestinationIds([1]));
    $this->assertEquals([
      [102, 'en'],
    ], $id_map->lookupDestinationIds([2]));
    $this->assertEquals([], $id_map->lookupDestinationIds([99]));
    // Lookup by partial associative list.
    $this->assertEquals([
      [101, 'en'],
      [101, 'fr'],
      [101, 'de'],
    ], $id_map->lookupDestinationIds(['nid' => 1]));
    $this->assertEquals([
      [102, 'en'],
    ], $id_map->lookupDestinationIds(['nid' => 2]));
    $this->assertEquals([], $id_map->lookupDestinationIds(['nid' => 99]));
    $this->assertEquals([
      [101, 'en'],
      [101, 'fr'],
      [101, 'de'],
    ], $id_map->lookupDestinationIds(['nid' => 1, 'language' => NULL]));
    $this->assertEquals([
      [102, 'en'],
    ], $id_map->lookupDestinationIds(['nid' => 2, 'language' => NULL]));
    // Out-of-order partial associative list.
    $this->assertEquals([
      [101, 'en'],
      [102, 'en'],
    ], $id_map->lookupDestinationIds(['language' => 'en']));
    $this->assertEquals([
      [101, 'fr'],
    ], $id_map->lookupDestinationIds(['language' => 'fr']));
    $this->assertEquals([], $id_map->lookupDestinationIds(['language' => 'zh']));
    // Error conditions.
    try {
      $id_map->lookupDestinationIds([1, 2, 3]);
      $this->fail('Too many source IDs should throw');
    }
    catch (MigrateException $e) {
      $this->assertEquals("Extra unknown items for map {$this->expectedMapTableName} in source IDs: array (\n  0 => 3,\n)", $e->getMessage());
    }
    try {
      $id_map->lookupDestinationIds(['nid' => 1, 'aaa' => '2']);
      $this->fail('Unknown source ID key should throw');
    }
    catch (MigrateException $e) {
      $this->assertEquals("Extra unknown items for map {$this->expectedMapTableName} in source IDs: array (\n  'aaa' => '2',\n)", $e->getMessage());
    }

    // Verify that we are looking up by source_id_hash when all source IDs are
    // passed in.
    $id_map->getDatabase()->update($id_map->mapTableName())
      ->condition('sourceid1', 1)
      ->condition('sourceid2', 'en')
      ->fields([TestSmartSqlIdMap::SOURCE_IDS_HASH => uniqid()])
      ->execute();
    $this->assertNotEquals([
      [101, 'en'],
    ], $id_map->lookupDestinationIds([1, 'en']));
  }

  /**
   * Tests setting a row source_row_status to STATUS_NEEDS_UPDATE.
   */
  public function testSetUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = [
        'sourceid1' => 'source_value_' . $status,
        'source_ids_hash' => $this->getIdMap()->getSourceIdsHash($source),
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      ];
    }
    // Assert that test values exist.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);
    // Mark each row as STATUS_NEEDS_UPDATE.
    foreach ($row_statuses as $status) {
      $id_map->setUpdate(['source_id_property' => 'source_value_' . $status]);
    }
    // Update expected results.
    foreach ($expected_results as $key => $value) {
      $expected_results[$key]['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    }
    // Assert that updated expected values match.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);
    // Assert an exception is thrown when source identifiers are not provided.
    try {
      $id_map->setUpdate([]);
      $this->assertFalse(FALSE, 'MigrateException not thrown, when source identifiers were provided to update.');
    }
    catch (MigrateException $e) {
      $this->assertTrue(TRUE, "MigrateException thrown, when source identifiers were not provided to update.");
    }
  }

  /**
   * Tests prepareUpdate().
   */
  public function testPrepareUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];

    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = [
        'sourceid1' => 'source_value_' . $status,
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      ];
    }

    // Assert that test values exist.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);

    // Mark all rows as STATUS_NEEDS_UPDATE.
    $id_map->prepareUpdate();

    // Update expected results.
    foreach ($expected_results as $key => $value) {
      $expected_results[$key]['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    }
    // Assert that updated expected values match.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);
  }

  /**
   * Tests the getQualifiedMapTable method with a prefixed database.
   */
  public function testGetQualifiedMapTablePrefix() {
    $connection_options = [
      'database' => ':memory:',
      'prefix' => 'prefix',
    ];
    $pdo = Connection::open($connection_options);
    $this->database = new Connection($pdo, $connection_options);
    $qualified_map_table = $this->getIdMap()->getQualifiedMapTableName();
    // The SQLite driver is a special flower. It will prefix tables with
    // PREFIX.TABLE, instead of the standard PREFIXTABLE.
    // @see \Drupal\Core\Database\Driver\sqlite\Connection::__construct()
    $this->assertEquals("prefix.{$this->expectedPrefixedMapTableName}", $qualified_map_table);
  }

  /**
   * Retrieves the contents of an ID map.
   *
   * @return array
   *   The contents of an ID map.
   */
  private function getIdMapContents() {
    $result = $this->database
      ->select($this->getIdMap()->getQualifiedMapTableName(), 't')
      ->fields('t')
      ->execute();

    // The return value needs to be countable, or it will fail certain
    // assertions. iterator_to_array() will not suffice because it won't
    // respect the PDO fetch mode, if specified.
    $contents = [];
    foreach ($result as $row) {
      $contents[] = (array) $row;
    }
    return $contents;
  }

  /**
   * Tests the delayed creation of the "map" and "message" migrate tables.
   */
  public function testMapTableCreation() {
    $id_map = $this->getIdMap();
    $map_table_name = $id_map->mapTableName();
    $message_table_name = $id_map->messageTableName();

    // Check that tables names do exist.
    $this->assertEquals($this->expectedMapTableName, $map_table_name);
    $this->assertEquals($this->expectedMessageTableName, $message_table_name);

    // Check that tables don't exist.
    $this->assertFalse($this->database->schema()->tableExists($map_table_name));
    $this->assertFalse($this->database->schema()->tableExists($message_table_name));

    $id_map->getDatabase();

    // Check that tables do exist.
    $this->assertTrue($this->database->schema()->tableExists($map_table_name));
    $this->assertTrue($this->database->schema()->tableExists($message_table_name));
  }

  /**
   * Tests the getRowByDestination method.
   */
  public function testGetRowByDestination() {
    try {
      parent::testGetRowByDestination();
    }
    catch (ExpectationFailedException $exception) {
      // The parent test method may throw an expectation failed exception,
      // because expects that the sql ID map plugin violates its interface,
      // meaning that it returns FALSE.
      $message_is_about_actual_array_is_not_false =
        (
          preg_match('/^Failed asserting that Array .*/', $exception->getMessage()) &&
          preg_match('/.* is false\.$/', $exception->getMessage())
        ) ||
        // TestCompatibilityTrait::assertFalse() (Drupal 8) first tests
        // emptyness.
        // @see \Drupal\TestTools\PhpUnitCompatibility\PhpUnit6\TestCompatibilityTrait::assertFalse()
        // @see \Drupal\TestTools\PhpUnitCompatibility\PhpUnit7\TestCompatibilityTrait::assertFalse()
        preg_match('/^Failed asserting that an array is empty.$/', $exception->getMessage());
      if (!$message_is_about_actual_array_is_not_false) {
        throw $exception;
      }
    }
    $id_map = $this->getIdMap();
    // This value does not exist, getRowByDestination should return an (empty)
    // array.
    // @see \Drupal\migrate\Plugin\MigrateIdMapInterface::getRowByDestination()
    $missing_result_row = $id_map->getRowByDestination([
      'destination_id_property' => 'invalid_destination_id_property',
    ]);
    $this->assertIsArray($missing_result_row);
    // The destination ID values array does not contain all the destination ID
    // keys, we expect an empty array.
    $invalid_result_row = $id_map->getRowByDestination([
      'invalid_destination_key' => 'invalid_destination_id_property',
    ]);
    $this->assertIsArray($invalid_result_row);
  }

}
