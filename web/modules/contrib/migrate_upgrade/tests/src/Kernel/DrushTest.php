<?php

namespace Drupal\Tests\migrate_upgrade\Kernel {

  use Drupal\Component\Plugin\PluginBase;
  use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
  use Drupal\migrate_drupal\MigrationConfigurationTrait;
  use Drupal\migrate_plus\Entity\Migration;
  use Drupal\migrate_upgrade\Commands\MigrateUpgradeCommands;
  use Drupal\Tests\DeprecatedModulesTestTrait;
  use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
  use Drupal\Tests\migrate_drupal\Traits\CreateMigrationsTrait;

  /**
   * Tests the drush command runner for migrate upgrade.
   *
   * @group migrate_upgrade
   *
   * @requires module migrate_plus
   */
  class DrushTest extends MigrateDrupalTestBase {
    use CreateMigrationsTrait;
    use FileSystemModuleDiscoveryDataProviderTrait;
    use MigrationConfigurationTrait;

    /**
     * The migration plugin manager.
     *
     * @var \Drupal\migrate\Plugin\MigrationPluginManager
     */
    protected $migrationManager;

    /**
     * The Migrate Upgrade Command drush service.
     *
     * @var \Drupal\migrate_upgrade\Commands\MigrateUpgradeCommands
     */
    protected $commands;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
      // Enable all modules.
      self::$modules = array_merge(array_keys($this->coreModuleListDataProvider()), [
        'migrate_plus',
        'migrate_upgrade',
      ]);
      self::$modules = array_diff(self::$modules, ['block_place']);
      parent::setUp();
      $this->installConfig(self::$modules);
      $this->installEntitySchema('migration_group');
      $this->installEntitySchema('migration');
      $this->migrationManager = \Drupal::service('plugin.manager.migration');
      $this->state = $this->container->get('state');

      // Mocks the logger channel and factory because drush is not available
      // to use directly, and the Drupal loggers do not implement the "ok"
      // level.
      $loggerProphet = $this->prophesize('\Drush\Log\Logger');
      $loggerFactoryProphet = $this->prophesize('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
      $loggerFactoryProphet->get('drush')->willReturn($loggerProphet->reveal());

      $this->commands = new MigrateUpgradeCommands($this->state, $loggerFactoryProphet->reveal());
    }

    /**
     * Tests that all D6 migrations are generated as migrate plus entities.
     */
    public function testD6Migrations(): void {
      $this->drupal6Migrations();
      $options = [
        'configure-only' => TRUE,
        'legacy-db-key' => $this->sourceDatabase->getKey(),
      ];
      $this->commands->upgrade($options);

      $migrate_plus_migrations = Migration::loadMultiple();
      $migrations = $this->getMigrations($this->sourceDatabase->getKey(), 6);
      $this->assertMigrations($migrations, $migrate_plus_migrations);
      $optional = array_flip($migrate_plus_migrations['upgrade_d6_url_alias']->toArray()['migration_dependencies']['optional']);
      $node_migrations = array_intersect_key(['upgrade_d6_node_translation_page' => TRUE, 'upgrade_d6_node_complete_page' => TRUE], $optional);
      $this->assertNotEmpty($node_migrations);
    }

    /**
     * Tests that all D7 migrations are generated as migrate plus entities.
     */
    public function testD7Migrations(): void {
      $this->drupal7Migrations();
      $this->sourceDatabase->update('system')
        ->fields(['status' => 1])
        ->condition('name', 'profile')
        ->execute();
      $options = [
        'configure-only' => TRUE,
        'legacy-db-key' => $this->sourceDatabase->getKey(),
      ];
      $this->commands->upgrade($options);

      $migrate_plus_migrations = Migration::loadMultiple();
      $migrations = $this->getMigrations($this->sourceDatabase->getKey(), 7);
      $this->assertMigrations($migrations, $migrate_plus_migrations);
      $optional = array_flip($migrate_plus_migrations['upgrade_d7_url_alias']->toArray()['migration_dependencies']['optional']);
      $node_migrations = array_intersect_key(['upgrade_d7_node_translation_page' => TRUE, 'upgrade_d7_node_complete_page' => TRUE], $optional);
      $this->assertNotEmpty($node_migrations);
    }

    /**
     * Asserts that all migrations are exported as migrate plus entities.
     *
     * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
     *   The migrations.
     * @param \Drupal\migrate_plus\Entity\MigrationInterface[] $migrate_plus_migrations
     *   The migrate plus config entities.
     */
    protected function assertMigrations(array $migrations, array $migrate_plus_migrations): void {
      foreach ($migrations as $id => $migration) {
        $migration_id = 'upgrade_' . str_replace(PluginBase::DERIVATIVE_SEPARATOR, '_', $migration->id());
        $this->assertArrayHasKey($migration_id, $migrate_plus_migrations);
      }
      $this->assertCount(count($migrations), $migrate_plus_migrations);
    }

  }

}

namespace {
  if (!function_exists('dt')) {

    /**
     * Stub for dt().
     *
     * @param string $message
     *   The text.
     * @param array $replace
     *   The replacement values.
     *
     * @return string
     *   The text.
     */
    function dt($message, array $replace = []) {
      return strtr($message, $replace);
    }

  }

  if (!function_exists('drush_op')) {

    /**
     * Stub for drush_op.
     *
     * @param callable $callable
     *   The function to call.
     */
    function drush_op(callable $callable) {
      $args = func_get_args();
      array_shift($args);
      call_user_func_array($callable, $args);
    }

  }

}
