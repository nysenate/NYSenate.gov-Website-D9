<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\media_migration\Plugin\migrate\source\d7\MediaMigrationDatabaseTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media_migration\Traits\Issue3260111FixedTrait;
use Drupal\Tests\migmag\Traits\MigMagMigrationTestDatabaseTrait;
use Drush\TestTraits\DrushTestTrait;

/**
 * Base class for testing media migrations executed with Drush.
 */
abstract class DrushTestBase extends BrowserTestBase {

  use DrushTestTrait;
  use MigMagMigrationTestDatabaseTrait;
  use Issue3260111FixedTrait;
  use MediaMigrationDatabaseTrait;

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'linkit',
    'media',
    'media_migration',
    'media_migration_test_oembed',
    'media_migration_tools',
    'migrate_tools',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures/drupal7_media.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if ($this->connectionIsSqlite() && $this->coreVersionMightHaveRegression3260111()) {
      $this->markTestSkipped(
        'Migrating with joinable SQLite databases is broken with core versions 9.3+.'
      );
    }

    $shortcut_links = \Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple();
    foreach ($shortcut_links as $shortcut) {
      $shortcut->delete();
    }
    $shortcut_sets = \Drupal::entityTypeManager()->getStorage('shortcut_set')->loadMultiple();
    foreach ($shortcut_sets as $shortcut_set) {
      $shortcut_set->delete();
    }

    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->uninstall(['shortcut']);

    // The 'default' and 'migrate' DB key strings are 7 chars long. Using a
    // different length ensures that the migration connection key used for
    // testing cannot be 'default' or 'migrate'.
    $source_db_key = $this->randomMachineName(8);
    $this->createSourceMigrationConnection($source_db_key);
    $this->sourceDatabase = Database::getConnection('default', $source_db_key);
    $this->loadFixture($this->getFixtureFilePath());

    // Delete preexisting media types.
    $media_types = $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->loadMultiple();
    foreach ($media_types as $media_type) {
      $media_type->delete();
    }

    // Delete preexisting node types.
    $node_types = $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($node_types as $node_type) {
      $node_type->delete();
    }

    $this->resetAll();
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * This method is the exact copy of
   * Drupal\Tests\migrate\Kernel\MigrateTestBase::loadFixture().
   *
   * @param string $path
   *   Path to the dump file.
   */
  protected function loadFixture($path) {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    require $path;

    Database::setActiveConnection($default_db);
  }

  /**
   * Asserts that the expected rows are present in the output of migrate:status.
   *
   * @param string[] $expected_lines
   *   The expected lines of the migrate:status command's response.
   */
  protected function assertDrushMigrateStatusOutputHasAllLines(array $expected_lines) {
    $drush_output_array = explode("\n", $this->getSimplifiedOutput());
    $filtered_output = array_reduce($drush_output_array, function (array $carry, $output_line) {
      if (!preg_match('/^[-\s]+$/', $output_line)) {
        $carry[] = $output_line;
      }
      return $carry;
    }, []);
    $missing_from_output = array_diff($filtered_output, $expected_lines);
    $extra_output = array_diff($expected_lines, $filtered_output);

    $this->assertEmpty($extra_output);
    $this->assertEquals(['Group Migration ID'], $missing_from_output);
  }

  /**
   * Checks whether the actual DB connection is a PostgreSql connection.
   *
   * @return bool
   *   Whether the actual DB connection is a PostgreSql connection.
   */
  protected function connectionIsPostgreSql(): bool {
    return \Drupal::database()->getConnectionOptions()['driver'] === 'pgsql';
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->cleanupSourceMigrateConnection($this->sourceDatabase->getKey());
    parent::tearDown();
  }

}
