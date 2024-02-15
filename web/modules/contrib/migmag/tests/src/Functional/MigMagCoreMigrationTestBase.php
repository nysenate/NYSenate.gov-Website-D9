<?php

namespace Drupal\Tests\migmag\Functional;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\migmag\Traits\MigMagExportTrait;
use Drupal\Tests\migrate_drupal_ui\Functional\d7\Upgrade7Test;

/**
 * Base class for Migrate Magician tests extending Upgrate7Tests.
 */
abstract class MigMagCoreMigrationTestBase extends Upgrade7Test {

  use MigMagExportTrait;

  /**
   * List of test methods to ignore.
   *
   * @const string[]
   */
  const IGNORED_TEST_METHODS = [
    'testUpgradeAndIncremental' => "Core 9.2+ uses this 'testUpgradeAndIncremental' method for testing UI migration, while 9.1- uses 'testMigrateUpgradeExecute'. '%s' standardizes this by providing the 'executeDrupal7Migration' method.",
    'testMigrateUpgradeExecute' => "Core 9.1- uses this 'testUpgradeAndIncremental' method for testing UI migration, while 9.2+ uses 'testUpgradeAndIncremental'. '%s' standardizes this by providing the 'executeDrupal7Migration' method.",
  ];

  /**
   * {@inheritdoc}
   */
  protected function createContentPostUpgrade() {}

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return $this->getEntityCounts();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (array_key_exists($this->getName(), static::IGNORED_TEST_METHODS)) {
      $message_template = static::IGNORED_TEST_METHODS[$this->getName()] ?? NULL;
      $this->markTestSkipped(
        $message_template
          ? sprintf(
            $message_template,
            __CLASS__
            )
          : ''
      );
    }

    parent::setUp();
  }

  /**
   * Executes Drupal 7 migration with Migrate Drupal UI.
   */
  public function executeDrupal7Migration() {
    if (is_callable('parent::testMigrateUpgradeExecute')) {
      parent::testMigrateUpgradeExecute();
    }
    else {
      parent::testUpgradeAndIncremental();
    }
  }

  /**
   * Installs every migmag module, excluding test modules.
   */
  protected function enableAllMigmagModule() {
    $module_data = \Drupal::service('extension.list.module')->reset()->getList();
    $migmag_root = dirname($module_data['migmag']->getPathname());
    $migmag_modules = array_keys(
      array_filter(
        $module_data,
        function (Extension $extension) use ($migmag_root) {
          return strpos($extension->getPathname(), $migmag_root . DIRECTORY_SEPARATOR) === 0 &&
            strpos($extension->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) === FALSE &&
            $extension->getType() === 'module';
        }
      )
    );

    $this->assertEquals(
      [
        'migmag',
        'migmag_callback_upgrade',
        'migmag_menu_link_migrate',
        'migmag_process',
        'migmag_process_lookup_replace',
        'migmag_rollbackable',
        'migmag_rollbackable_replace',
      ],
      $migmag_modules
    );

    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install($migmag_modules);
    $this->resetAll();
  }

}
