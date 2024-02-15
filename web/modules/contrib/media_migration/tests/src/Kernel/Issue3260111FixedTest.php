<?php

namespace Drupal\Tests\media_migration\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Tests\media_migration\Traits\Issue3260111FixedTrait;

/**
 * Tests whether https://drupal.org/i/3260111 is fixed.
 *
 * @group media_migration
 */
class Issue3260111FixedTest extends KernelTestBase {

  use Issue3260111FixedTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'migrate',
    'migrate_drupal',
  ];

  /**
   * The database connection of the migration source.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if (!$this->connectionIsSqlite()) {
      $this->markTestSkipped(
        'Test should be evaluated only with SQLite databases.'
      );
    }
    $this->prepareTest();
  }

  /**
   * Tests whether source and ID map tables are truly joinable.
   */
  public function testJoinableMigrationTables(): void {
    $manager = \Drupal::service('plugin.manager.migration');
    assert($manager instanceof MigrationPluginManagerInterface);
    $migration = $manager->createInstance('d7_user_role');
    assert($migration instanceof MigrationInterface);

    // In this test, source and the ID map plugin tables must be joinable.
    $source = $migration->getSourcePlugin();
    assert($source instanceof SqlBase);
    $source_ref = new \ReflectionObject($source);
    $map_joinable_method = $source_ref->getMethod('mapJoinable');
    $map_joinable_method->setAccessible(TRUE);
    $this->assertTrue($map_joinable_method->invoke($source));

    // ID map is initialized, source and ID map tables are joinable, so the next
    // statement shouldn't throw database exception.
    try {
      $iterator_array = iterator_to_array($source, FALSE);
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->assertTrue($this->coreVersionMightHaveRegression3260111());
      return;
    }

    $this->assertIsArray($iterator_array);
  }

  /**
   * Prepares the migration test.
   *
   * Creates a 'joinable' source migration connection and loads core's Drupal 7
   * migration database fixture into this source connection.
   */
  protected function prepareTest(): void {
    $this->createSourceMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate');
    $this->loadFixture(implode(
      DIRECTORY_SEPARATOR,
      [
        DRUPAL_ROOT,
        'core',
        'modules',
        'migrate_drupal',
        'tests',
        'fixtures',
        'drupal7.php',
      ]
    ));
  }

  /**
   * Creates a database connection for migration tests.
   *
   * This method is a slightly modified copy of the base migration kernel test
   * class' method - it also supports both core 9.2.x and 9.3+.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::createMigrationConnection()
   */
  protected function createSourceMigrationConnection(): void {
    // If the backup already exists, something went terribly wrong.
    // This case is possible, because database connection info is a static
    // global state construct on the Database class, which at least persists
    // for all test methods executed in one PHP process.
    if (Database::getConnectionInfo('simpletest_original_migrate')) {
      throw new \RuntimeException("Bad Database connection state: 'simpletest_original_migrate' connection key already exists. Broken test?");
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('migrate');
    if ($connection_info) {
      Database::renameConnection('migrate', 'simpletest_original_migrate');
    }
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $prefix = is_array($value['prefix']) ? $value['prefix']['default'] : $value['prefix'];
      // Simpletest uses 7 character prefixes at most so this can't cause
      // collisions.
      if (is_string($connection_info[$target]['prefix'])) {
        $connection_info[$target]['prefix'] = $prefix . '0';
        continue;
      }

      $connection_info[$target]['prefix']['default'] = $prefix . '0';
      // Add the original simpletest prefix so SQLite can attach its database.
      // @see \Drupal\Core\Database\Driver\sqlite\Connection::init()
      $connection_info[$target]['prefix'][$value['prefix']['default']] = $prefix;
      break;
    }
    Database::addConnectionInfo('migrate', 'default', $connection_info['default']);
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * This method is the copy of the base Drupal migration kernel test class'
   * method.
   *
   * @param string $path
   *   Path to the dump file.
   *
   * @see \Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase::loadFixture()
   */
  protected function loadFixture($path): void {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    require $path;

    Database::setActiveConnection($default_db);
  }

}
