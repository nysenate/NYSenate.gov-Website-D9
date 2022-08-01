<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test the splitting and merging.
 *
 * These are the integration tests to assert that the module has the behaviour
 * on import and export that we expect. This is supposed to not go into internal
 * details of how config split achieves this.
 *
 * @group config_split_new
 */
class SplitMergeTest extends KernelTestBase {

  use ConfigStorageTestTrait;
  use SplitTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'language',
    'user',
    'node',
    'field',
    'text',
    'config',
    'config_test',
    'config_exclude_test',
    'config_split',
    'config_filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Make sure there is a good amount of config to play with.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    // The module config_test has translations and config_exclude_test has
    // config with dependencies.
    $this->installConfig(['system', 'field', 'config_test', 'config_exclude_test']);

    // Set up multilingual.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Test a simple export split.
   */
  public function testSimpleSplitExport() {
    // Simple split with default configuration.
    $config = $this->createSplitConfig('test_split', [
      'folder' => Settings::get('file_public_path') . '/config/split',
      'module' => ['config_test' => 0],
    ]);

    $active = $this->getActiveStorage();
    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up expectations.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);
        if ($name === 'core.extension') {
          // We split off the module.
          unset($data['module']['config_test']);
        }

        if (strpos($name, 'config_test') !== FALSE || in_array($name, ['system.menu.exclude_test', 'system.menu.indirect_exclude_test'])) {
          // Expect config that depends on config_test directly and indirectly
          // to be split off.
          $expectedSplit->write($name, $data);
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }

    static::assertStorageEquals($expectedExport, $this->getExportStorage());
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

  /**
   * Test complete and conditional split export.
   */
  public function testCompleteAndConditionalSplitExport() {

    $config = $this->createSplitConfig('test_split', [
      'folder' => Settings::get('file_public_path') . '/config/split',
      'blacklist' => ['config_test.types'],
      'graylist' => ['config_test.system'],
      'graylist_skip_equal' => TRUE,
    ]);

    $active = $this->getActiveStorage();
    // Export the configuration to sync without filtering.
    $this->copyConfig($active, $this->getSyncFileStorage());

    // Change the gray listed config to see if it is exported the same.
    $originalSystem = $this->config('config_test.system')->getRawData();
    $this->config('config_test.system')->set('foo', 'baz')->save();

    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up the expected data.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);
        if ($name === 'config_test.types') {
          $expectedSplit->write($name, $data);
        }
        elseif ($name === 'config_test.system') {
          // We only changed the config in the default collection.
          if ($collection === StorageInterface::DEFAULT_COLLECTION) {
            $expectedSplit->write($name, $data);
            $expectedExport->write($name, $originalSystem);
          }
          else {
            // The option "skip equal" is false, write to export only.
            $expectedExport->write($name, $data);
          }
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }

    static::assertStorageEquals($expectedExport, $this->getExportStorage());
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config));

    // Change the config.
    $config->set('blacklist', ['config_test.system'])->set('graylist', [])->save();

    // Update expectations.
    $expectedExport->write($config->getName(), $config->getRawData());
    $expectedExport->write('config_test.types', $active->read('config_test.types'));
    $expectedSplit->delete('config_test.types');
    // Update multilingual expectations.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);

      $expectedExport->delete('config_test.system');
      $expectedSplit->write('config_test.system', $active->read('config_test.system'));
    }

    static::assertStorageEquals($expectedExport, $this->getExportStorage());
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

  /**
   * Test complete and conditional split export with modules.
   */
  public function testConditionalSplitWithModuleConfig() {

    $config = $this->createSplitConfig('test_split', [
      'folder' => Settings::get('file_public_path') . '/config/split',
      'module' => ['config_test' => 0],
      'graylist' => ['config_test.system'],
      'graylist_skip_equal' => FALSE,
    ]);

    $active = $this->getActiveStorage();
    // Export the configuration to sync without filtering.
    $this->copyConfig($active, $this->getSyncFileStorage());

    // Change the gray listed config to see if it is exported the same.
    $originalSystem = $this->config('config_test.system')->getRawData();
    $this->config('config_test.system')->set('foo', 'baz')->save();

    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up the expected data.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);
        if ($name === 'core.extension') {
          unset($data['module']['config_test']);
        }

        if ($name === 'config_test.system') {
          // We only changed the config in the default collection.
          if ($collection === StorageInterface::DEFAULT_COLLECTION) {
            // The unchanged config is exported, the changed one is split.
            $expectedSplit->write($name, $data);
            $expectedExport->write($name, $originalSystem);
          }
          else {
            // The option "skip equal" is false, write to both.
            $expectedSplit->write($name, $data);
            $expectedExport->write($name, $data);
          }
        }
        elseif (strpos($name, 'config_test') !== FALSE || in_array($name, ['system.menu.exclude_test', 'system.menu.indirect_exclude_test'])) {
          // Expect config that depends on config_test directly and indirectly
          // to be split off.
          $expectedSplit->write($name, $data);
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }

    static::assertStorageEquals($expectedExport, $this->getExportStorage());
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

  /**
   * Test that dependencies are split too.
   */
  public function testIncludeDependency() {
    $config = $this->createSplitConfig('test_split', [
      'graylist' => ['system.menu.exclude_test'],
      'graylist_dependents' => TRUE,
      'graylist_skip_equal' => TRUE,
    ]);

    $active = $this->getActiveStorage();
    // Export the configuration to sync without filtering.
    $this->copyConfig($active, $this->getSyncFileStorage());

    // Change only the indirectly dependent config.
    $originalSystem = $this->config('system.menu.indirect_exclude_test')->getRawData();
    $this->config('system.menu.indirect_exclude_test')->set('label', 'Split Test')->save();

    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up the expected data.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);

        if ($name === 'system.menu.indirect_exclude_test') {
          // We only changed the config in the default collection.
          if ($collection === StorageInterface::DEFAULT_COLLECTION) {
            // The unchanged value is in export, the changed value is split.
            $expectedSplit->write($name, $data);
            $expectedExport->write($name, $originalSystem);
          }
          else {
            // The option "skip equal" is false, write to export only.
            $expectedExport->write($name, $data);
          }
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }

    static::assertStorageEquals($expectedExport, $this->getExportStorage());
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());

    // If we don't include the dependants then the split will be empty.
    $config->set('graylist_dependents', FALSE)->save();
    static::assertStorageEquals($active, $this->getExportStorage());
    static::assertStorageEquals(new MemoryStorage(), $this->getSplitPreviewStorage($config));
  }

}
