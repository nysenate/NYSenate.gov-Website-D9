<?php

namespace Drupal\Tests\media_migration\Unit\Plugin\migrate\process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\media_migration\MediaMigrationUuidOracleInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Base class for testing Media Migration's migrate process plugins.
 */
abstract class ProcessTestBase extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected $migrationConfiguration = [
    'id' => 'test_content_migration',
    'destination' => [
      'plugin' => 'entity:node',
    ],
  ];

  /**
   * A source database to test with.
   *
   * @var array[]
   */
  protected $testDatabase = [];

  /**
   * A media migration UUID oracle object prophecy.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface
   */
  protected $uuidOracle;

  /**
   * The migration the process plugin is tested with.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * A SqlBase migration source plugin object prophecy.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface
   */
  protected $sourcePlugin;

  /**
   * The process plugin we test.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $plugin;

  /**
   * A logger channel object prophecy.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface
   */
  protected $logger;

  /**
   * A migrate lookup prophecy.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface
   */
  protected $migrateLookup;

  /**
   * Entity type manager prophecy.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->uuidOracle = $this->prophesize(MediaMigrationUuidOracleInterface::class);
    $this->uuidOracle->getMediaUuid(1)->willReturn('jpeg1-uuid');
    $this->uuidOracle->getMediaUuid(2)->willReturn('png2-uuid');
    $this->uuidOracle->getMediaUuid(3)->willReturn('svg3-uuid');

    $this->row->method('getSourceIdValues')->willReturn([
      'nid' => 123,
      'vid' => 456,
      'language' => 'hu',
    ]);

    $this->sourcePlugin = $this->prophesize(DrupalSqlBase::class);
    $this->sourcePlugin
      ->getDatabase()
      ->willReturn($this->getDatabase($this->testDatabase));

    $this->migration = $this->getMigration();
    $this->migration
      ->method('getSourcePlugin')
      ->willReturn($this->sourcePlugin->reveal());
    $this->migration
      ->method('getDestinationConfiguration')
      ->willReturn($this->migrationConfiguration['destination'] ?? []);

    $this->logger = $this->prophesize(LoggerChannelInterface::class);

    $this->migrateLookup = $this->prophesize(MigrateLookupInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    $container = new ContainerBuilder();
    $migration_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $migration_manager->getDefinitions()->willReturn([]);
    $container->set('plugin.manager.migration', $migration_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Sets an in-memory Settings variable.
   *
   * See of KernelTestBase::setSetting().
   *
   * @param string $name
   *   The name of the setting to set.
   * @param bool|string|int|array|null $value
   *   The value to set. Note that array values are replaced entirely; use
   *   \Drupal\Core\Site\Settings::get() to perform custom merges.
   */
  protected function setSetting($name, $value) {
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings[$name] = $value;
    new Settings($settings);
  }

}
