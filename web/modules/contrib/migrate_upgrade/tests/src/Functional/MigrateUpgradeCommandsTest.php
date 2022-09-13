<?php

namespace Drupal\Tests\migrate_upgrade\Functional;

use Drupal\Core\Plugin\PluginBase;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Execute drush on fully functional website.
 *
 * @group migrate_upgrade
 */
class MigrateUpgradeCommandsTest extends MigrateUpgradeTestBase {
  use DrushTestTrait;
  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Enable all modules.
    self::$modules = array_merge(array_keys($this->coreModuleListDataProvider()), [
      'migrate_plus',
      'migrate_upgrade',
    ]);
    self::$modules = array_diff(self::$modules, [
      'block_place',
      'simpletest',
      'migrate_drupal_multilingual',
      'entity_reference',
    ]);
    parent::setUp();
  }

  /**
   * Test module using Drupal fixture.
   *
   * @param int $drupal_version
   *   The major Drupal version.
   *
   * @dataProvider majorDrupalVersionsDataProvider
   */
  public function testDrupalConfigureUpgrade($drupal_version): void {
    $migrate_drupal_path = \Drupal::service('extension.list.module')->getPath('migrate_drupal');
    $this->loadFixture($migrate_drupal_path . "/tests/fixtures/drupal{$drupal_version}.php");
    $prefix = 'upgrade_legacy_';
    $this->executeMigrateUpgrade([
      'configure-only' => NULL,
      'migration-prefix' => $prefix,
    ]);
    $expected = [
      "{$prefix}action_settings" => [
        'original' => 'action_settings',
        'generated' => "{$prefix}action_settings",
      ],
      "{$prefix}book_settings" => [
        'original' => 'book_settings',
        'generated' => "{$prefix}book_settings",
      ],
    ];
    // Replacement for assertArraySubset that was deprecated.
    $this->assertEquals($this->getOutputFromJSON(), array_replace_recursive($expected, $this->getOutputFromJSON()));
    $migrate_plus_migrations = Migration::loadMultiple();
    $migrations = $this->getMigrations($this->sourceDatabase->getKey(), $drupal_version);
    $this->assertMigrations($prefix, $migrations, $migrate_plus_migrations);
    $optional = array_flip(
      $migrate_plus_migrations["{$prefix}d{$drupal_version}_url_alias"]
        ->toArray()['migration_dependencies']['optional']
    );
    $node_migrations = array_intersect_key(["{$prefix}d{$drupal_version}_node_translation_page" => TRUE, "{$prefix}d{$drupal_version}_node_complete_page" => TRUE], $optional);
    $this->assertNotEmpty($node_migrations);
  }

  /**
   * The major Drupal versions to test an upgrade.
   *
   * @return array
   *   The major version.
   */
  public function majorDrupalVersionsDataProvider(): array {
    $version = [];
    $version['drupal 6'] = [6];
    $version['drupal 7'] = [7];
    return $version;
  }

  /**
   * Execute Drush migrate:upgrade command.
   *
   * @param array $options
   *   The Drush command options.
   */
  protected function executeMigrateUpgrade(array $options = []): void {
    $options += [
      'format' => 'json',
      'fields' => '*',
    ];
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $options['legacy-db-url'] = $this->convertDbSpecUrl($connection_options);
    if (!empty($connection_options['prefix']['default'])) {
      $options['legacy-db-prefix'] = $connection_options['prefix']['default'];
    }
    // This changed in 9.3 with https://www.drupal.org/node/3106531.
    elseif (array_key_exists('prefix', $connection_options)) {
      $options['legacy-db-prefix'] = $connection_options['prefix'];
    }
    $this->drush('migrate:upgrade', [], $options);
  }

  /**
   * Asserts that all migrations are exported as migrate_plus entities.
   *
   * @param string $prefix
   *   The migration id prefix.
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   The migrations.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $migrate_plus_migrations
   *   The migrate plus config entities.
   */
  protected function assertMigrations($prefix, array $migrations, array $migrate_plus_migrations): void {
    // This filters to remove duplicate migrations that have an embedded data
    // source and therefore are always available.
    $available_migrations = array_flip(array_map(static function (MigrationInterface $migration) use ($prefix) {
      if ((strpos($migration->id(), $prefix) === 0)) {
        return $migration->id();
      }
      return $prefix . str_replace(PluginBase::DERIVATIVE_SEPARATOR, '_', $migration->id());
    }, $migrations));
    $this->assertEmpty(array_diff_key($available_migrations, $migrate_plus_migrations));
  }

  /**
   * Convert DB spec into a DB URL.
   *
   * @param array $db_spec
   *   The DB spec.
   *
   * @return string
   *   The DB URL.
   */
  protected function convertDbSpecUrl(array $db_spec): string {
    // If it's a sqlite database, pick the database path and we're done.
    if ($db_spec['driver'] === 'sqlite') {
      return 'sqlite://' . $db_spec['database'];
    }
    // Ex. mysql://username:password@localhost:3306/databasename#table_prefix.
    $url = $db_spec['driver'] . '://' . $db_spec['username'] . ':' . $db_spec['password'] . '@' . $db_spec['host'];
    if (isset($db_spec['port'])) {
      $url .= ':' . $db_spec['port'];
    }
    return $url . '/' . $db_spec['database'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath(): string {
    // Not needed.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts(): array {
    // Not needed.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    // Not needed.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths(): array {
    // Not needed.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental(): array {
    // Not needed.
    return [];
  }

}
