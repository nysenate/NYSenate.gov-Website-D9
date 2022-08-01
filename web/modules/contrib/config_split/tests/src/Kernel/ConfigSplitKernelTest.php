<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\config_filter\Config\FilteredStorage;
use Drupal\config_split\Form\ConfigSplitEntityForm;
use Drupal\config_split\Plugin\ConfigFilter\SplitFilter;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Integration test.
 *
 * @group config_split
 */
class ConfigSplitKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'config_test',
    'config_filter',
    'config_split',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['config_test']);
  }

  /**
   * Test that splits can be serialized.
   */
  public function testSerialisation() {

    $vfs = vfsStream::setup('split');
    $primary = new FileStorage($vfs->url() . '/sync');

    $folder_config = new Config('config_split.config_split.folder_split', $this->container->get('config.storage'), $this->container->get('event_dispatcher'), $this->container->get('config.typed'));
    $folder_config->initWithData([
      'id' => 'folder_split',
      'folder' => $vfs->url() . '/split',
      'module' => [],
      'theme' => [],
      'blacklist' => ['config_test.system'],
      'graylist' => [],
    ])->save();
    $folder_split = SplitFilter::create($this->container, ['config_name' => 'config_split.config_split.folder_split'], 'config_split:folder_split', []);

    $db_config = new Config('config_split.config_split.db_split', $this->container->get('config.storage'), $this->container->get('event_dispatcher'), $this->container->get('config.typed'));
    $db_config->initWithData([
      'id' => 'db_split',
      'folder' => '',
      'module' => [],
      'theme' => [],
      'blacklist' => ['config_test.types'],
      'graylist' => [],
    ])->save();
    $db_split = SplitFilter::create($this->container, ['config_name' => 'config_split.config_split.db_split'], 'config_split:db_split', []);

    // Create the filtered storage with a folder split and a database split.
    $filtered = new FilteredStorage($primary, [$folder_split, $db_split]);

    // Export the configuration.
    self::replaceAllStorageContents($this->container->get('config.storage'), $filtered);

    // Read from the split folder, the database and the sync directory.
    $test_system = $filtered->read('config_test.system');
    $test_types = $filtered->read('config_test.types');
    $test_validation = $filtered->read('config_test.validation');
    self::assertEquals($this->container->get('config.storage')->read('config_test.system'), $test_system);
    self::assertEquals($this->container->get('config.storage')->read('config_test.types'), $test_types);
    self::assertEquals($this->container->get('config.storage')->read('config_test.validation'), $test_validation);

    // Serialize and unserialize to make sure everything works.
    $serialized = serialize($filtered);
    $filtered = unserialize($serialized);

    // Assert reading the same values returns the same things afterwards.
    self::assertEquals($test_system, $filtered->read('config_test.system'));
    self::assertEquals($test_types, $filtered->read('config_test.types'));
    self::assertEquals($test_validation, $filtered->read('config_test.validation'));
  }

  /**
   * Test that the form checks the sync folder.
   *
   * @param string $split
   *   The split folder.
   * @param string $sync
   *   The sync folder.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider syncFolderIsConflictingProvider
   */
  public function testSyncFolderIsConflicting($split, $sync, $expected) {
    $settings = Settings::getAll();
    $settings['config_sync_directory'] = $sync;
    new Settings($settings);

    // Access the protected static function to test it.
    $reflection = new \ReflectionClass(ConfigSplitEntityForm::class);
    $method = $reflection->getMethod('isConflicting');
    $method->setAccessible(TRUE);

    self::assertEquals($expected, $method->invoke(NULL, $split));
  }

  /**
   * Provide the split and sync directories to compare.
   *
   * @return array
   *   The data.
   */
  public function syncFolderIsConflictingProvider() {
    return [
      ['../config/split', '../config/sync', FALSE],
      ['../config/config_split', '../config/config', FALSE],
      ['../config/sync/split', '../config/sync', TRUE],
      // We do not actually resolve the folder hierarchy.
      ['config/other/../sync', 'config/sync', FALSE],
    ];
  }


  /**
   * Copy the configuration from one storage to another and remove stale items.
   *
   * This method is the copy of how it worked prior to Drupal 9.4.
   * See https://www.drupal.org/node/3273823 for more details.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The configuration storage to copy from.
   * @param \Drupal\Core\Config\StorageInterface $target
   *   The configuration storage to copy to.
   */
  private static function replaceAllStorageContents(StorageInterface $source, StorageInterface &$target) {
    // Make sure there is no stale configuration in the target storage.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $target->getAllCollectionNames()) as $collection) {
      $target->createCollection($collection)->deleteAll();
    }

    // Copy all the configuration from all the collections.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $collection) {
      $source_collection = $source->createCollection($collection);
      $target_collection = $target->createCollection($collection);
      foreach ($source_collection->listAll() as $name) {
        $data = $source_collection->read($name);
        if ($data !== FALSE) {
          $target_collection->write($name, $data);
        }
        else {
          \Drupal::logger('config')->notice('Missing required data for configuration: %config', [
            '%config' => $name,
          ]);
        }
      }
    }

    // Make sure that the target is set to the same collection as the source.
    $target = $target->createCollection($source->getCollectionName());
  }


}
