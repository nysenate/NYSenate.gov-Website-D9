<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_usage\Functional\Update;

use Drupal\Core\Database\Connection;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Update path tests.
 *
 * @group entity_usage
 */
class UpdateTest extends UpdatePathTestBase {

  /**
   * The database connection.
   */
  protected Connection $connection;

  /**
   * The name of the test database.
   */
  protected string $databaseName;

  /**
   * The prefixed 'entity_usage' table.
   */
  protected string $tableName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Database\Connection $connection */
    $this->connection = \Drupal::service('database');
    if ($this->connection->databaseType() == 'pgsql') {
      $this->databaseName = 'public';
    }
    else {
      $this->databaseName = $this->connection->getConnectionOptions()['database'];
    }
    $this->tableName = ($this->connection->getConnectionOptions()['prefix'] ?? '') . 'entity_usage';
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/update_8206.php',
    ];
  }

  /**
   * @covers \entity_usage_update_8206
   * @see https://www.drupal.org/project/entity_usage/issues/3335488
   */
  public function testUpdate8206(): void {
    if (\Drupal::service('database')->databaseType() == 'sqlite') {
      $this->markTestSkipped('This test does not support the SQLite database driver.');
    }

    $this->assertColumnLength(128);
    $this->runUpdates();
    $this->assertColumnLength(255);
  }

  /**
   * Asserts the string entity ID columns max length.
   *
   * @param int $expected_length
   *   The expected max length.
   */
  protected function assertColumnLength(int $expected_length): void {
    $query = <<<QUERY
    SELECT character_maximum_length
    FROM information_schema.columns
    WHERE table_schema = '%s'
      AND table_name = '%s'
      AND column_name = '%s';
    QUERY;
    foreach (['target_id_string', 'source_id_string'] as $column) {
      $actual_length = $this->connection->query(sprintf($query, $this->databaseName, $this->tableName, $column))->fetchField();
      $this->assertEquals($expected_length, $actual_length);
    }
  }

}
