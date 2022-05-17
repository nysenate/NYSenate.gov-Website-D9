<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\config\Controller\ConfigController;
use Drupal\config_filter\Config\FilteredStorage;
use Drupal\Core\Archiver\Tar;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Test the cli service.
 *
 * @group config_split
 */
class ConfigSplitCliServiceTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'config',
    'config_test',
    'config_filter',
    'config_split',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'config_test']);

  }

  /**
   * Test that our export behaves the same as Drupal core without a split.
   */
  public function testVanillaExport() {
    // Set the "current user" to have "export configuration" permission.
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(27);
    $account->hasPermission('export configuration')->willReturn(TRUE);
    $this->container->set('current_user', $account->reveal());

    // Export the configuration the way drupal core does it.
    $configController = ConfigController::create($this->container);
    // Download and open the tar file.
    $file = $configController->downloadExport()->getFile()->openFile();
    $archive_data = $file->fread($file->getSize());
    // Save the tar file to unpack and read it.
    // See \Drupal\config\Tests\ConfigExportUITest::testExport()
    $uri = $this->container->get('file_system')->saveData($archive_data, 'temporary://config.tar.gz');
    $temp_folder = $this->container->get('file_system')->getTempDirectory();
    $file_target = StreamWrapperManager::getTarget($uri);
    $file_path = $temp_folder . '/' . $file_target;
    $archiver = new Tar($file_path);
    $this->assertNotEmpty($archiver->listContents(), 'Downloaded archive file is not empty.');

    // Extract the zip to a virtual file system.
    $core_export = vfsStream::setup('core-export');
    $archiver->extract($core_export->url());
    $this->assertNotEmpty($core_export->getChildren(), 'Successfully extract archive.');

    // Set a new virtual file system for the split export.
    $split_export = vfsStream::setup('split-export');
    $primary = new FileStorage($split_export->url());
    $this->assertEmpty($split_export->getChildren(), 'Before exporting the folder is empty.');

    // Do the export without a split configuration to the export folder.
    $this->container->get('config_split.cli')->export($primary);

    // Assert that the exported configuration is the same in both cases.
    $this->assertEquals(count($core_export->getChildren()), count($split_export->getChildren()), 'The same amount of config is exported.');
    foreach ($core_export->getChildren() as $child) {
      $name = $child->getName();
      if ($child->getType() == vfsStreamContent::TYPE_FILE) {
        // If it is a file we can compare the content.
        $this->assertEquals($child->getContent(), $split_export->getChild($name)->getContent(), 'The content of the exported file is the same.');
      }
    }

  }

  /**
   * Test a simple export split.
   */
  public function testSimpleSplitExport() {

    // Set the split stream up.
    $split = vfsStream::setup('split');
    $split_root = vfsStreamWrapper::getRoot();
    $primary = new FileStorage($split->url() . '/sync');
    $config = new Config('config_split.config_split.test_split', $this->container->get('config.storage'), $this->container->get('event_dispatcher'), $this->container->get('config.typed'));
    $config->initWithData([
      'id' => 'test_split',
      'folder' => $split->url() . '/split',
      'module' => ['config_test' => 0],
      'theme' => [],
      'blacklist' => [],
      'graylist' => [],
    ])->save();

    // Export the configuration the way Drupal core does.
    $vanilla = vfsStream::setup('vanilla');
    vfsStreamWrapper::getRoot();
    $vanilla_primary = new FileStorage($vanilla->url());
    $this->container->get('config_split.cli')->export($vanilla_primary);

    vfsStreamWrapper::setRoot($split_root);
    // Export the configuration without the test configuration.
    $filter = $this->container->get('plugin.manager.config_filter')->getFilterInstance('config_split:test_split');
    $storage = new FilteredStorage($primary, [$filter]);
    $this->container->get('config_split.cli')->export($storage);

    // Extract the configuration for easier comparison.
    $vanilla_config = [];
    foreach ($vanilla->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $vanilla_config[$child->getName()] = $child->getContent();
      }
    }

    $sync_config = [];
    foreach ($split->getChild('sync')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $sync_config[$child->getName()] = $child->getContent();
      }
    }

    $split_config = [];
    foreach ($split->getChild('split')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $split_config[$child->getName()] = $child->getContent();
      }
    }
    $this->assertNotEmpty($split_config, 'There is split off configuration.');
    $this->assertEquals(count($vanilla_config), count($sync_config) + count($split_config), 'All the config is still here.');

    foreach ($vanilla_config as $name => $content) {
      if ($name == 'core.extension.yml') {
        continue;
      }
      // All the filtered test config has config_test in its name.
      if (strpos($name, 'config_test') === FALSE) {
        $this->assertEquals($content, $sync_config[$name], 'The configuration is complete.');
        $this->assertNotContains($name, array_keys($split_config), 'And it does not exist in the other folder.');
      }
      else {
        $this->assertEquals($content, $split_config[$name], 'The configuration is complete.');
        $this->assertNotContains($name, array_keys($sync_config), 'And it does not exist in the other folder.');
      }
    }

    $this->assertNotFalse(strpos($vanilla_config['core.extension.yml'], 'config_test'), 'config_test is enabled.');
    $this->assertFalse(strpos($sync_config['core.extension.yml'], 'config_test'), 'config_test is not enabled.');

  }

  /**
   * Test blacklist and gray list export.
   */
  public function testGrayAndBlackListExport() {

    $split = vfsStream::setup('split');
    $primary = new FileStorage($split->url() . '/sync');
    $config = new Config('config_split.config_split.test_split', $this->container->get('config.storage'), $this->container->get('event_dispatcher'), $this->container->get('config.typed'));
    $config->initWithData([
      'id' => 'test_split',
      'folder' => $split->url() . '/split',
      'module' => [],
      'theme' => [],
      'blacklist' => ['config_test.types'],
      'graylist' => ['config_test.system'],
    ])->save();

    // Export the configuration like core.
    $this->container->get('config_split.cli')->export($primary);

    $original_config = [];
    foreach ($split->getChild('sync')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $original_config[$child->getName()] = $child->getContent();
      }
    }

    $this->assertFalse($split->hasChild('split'), 'The split directory is empty.');
    $this->assertTrue(isset($original_config['config_test.system.yml']), 'The graylisted config is exported.');
    $this->assertTrue(isset($original_config['config_test.types.yml']), 'The blacklisted config is exported.');

    // Change the gray listed config to see if it is exported the same.
    $this->config('config_test.system')->set('foo', 'baz')->save();

    // Export the configuration with filtering.
    $filter = $this->container->get('plugin.manager.config_filter')->getFilterInstance('config_split:test_split');
    $storage = new FilteredStorage($primary, [$filter]);
    $this->container->get('config_split.cli')->export($storage);

    $sync_config = [];
    foreach ($split->getChild('sync')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $sync_config[$child->getName()] = $child->getContent();
      }
    }

    $split_config = [];
    foreach ($split->getChild('split')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $split_config[$child->getName()] = $child->getContent();
      }
    }

    $this->assertTrue(isset($split_config['config_test.system.yml']), 'The graylisted config is exported to the split.');
    $this->assertTrue(isset($split_config['config_test.types.yml']), 'The blacklisted config is exported to the split.');
    $this->assertTrue(isset($sync_config['config_test.system.yml']), 'The graylisted config is exported to the sync.');
    $this->assertFalse(isset($sync_config['config_test.types.yml']), 'The blacklisted config is not exported to the sync.');

    $this->assertEquals($original_config['config_test.types.yml'], $split_config['config_test.types.yml'], 'The split blacklisted config is the same..');
    $this->assertEquals($original_config['config_test.system.yml'], $sync_config['config_test.system.yml'], 'The graylisted config stayed the same.');
    $this->assertNotEquals($original_config['config_test.system.yml'], $split_config['config_test.system.yml'], 'The split graylisted config is different.');

    // Change the filter.
    $config->initWithData([
      'folder' => $split->url() . '/split',
      'module' => [],
      'theme' => [],
      'blacklist' => ['config_test.system'],
      'graylist' => [],
    ])->save();

    // Export the configuration with filtering.
    $filter = $this->container->get('plugin.manager.config_filter')->getFilterInstance('config_split:test_split');
    $storage = new FilteredStorage($primary, [$filter]);
    $this->container->get('config_split.cli')->export($storage);

    $sync_config = [];
    foreach ($split->getChild('sync')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $sync_config[$child->getName()] = $child->getContent();
      }
    }

    $split_config = [];
    foreach ($split->getChild('split')->getChildren() as $child) {
      if ($child->getType() == vfsStreamContent::TYPE_FILE && $child->getName() != '.htaccess') {
        $split_config[$child->getName()] = $child->getContent();
      }
    }

    $this->assertFalse(isset($sync_config['config_test.system.yml']), 'The newly blacklisted config is removed.');
    $this->assertTrue(isset($split_config['config_test.system.yml']), 'The newly blacklisted config is exported to the split.');
    $this->assertFalse(isset($split_config['config_test.types.yml']), 'The config no longer blacklisted is not exported to the split.');
    $this->assertTrue(isset($sync_config['config_test.types.yml']), 'The config no longer blacklisted is exported to the sync.');

  }

}
