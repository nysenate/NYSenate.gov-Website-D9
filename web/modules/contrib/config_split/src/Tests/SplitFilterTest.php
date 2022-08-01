<?php

namespace Drupal\config_split\Tests;

use Drupal\config_split\Plugin\ConfigFilter\SplitFilter;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use Prophecy\Argument;

/**
 * Test filter plugin.
 *
 * @group config_split
 */
class SplitFilterTest extends UnitTestCase {

  /**
   * Test that the blacklist is correctly calculated.
   */
  public function testBlacklist() {
    $configuration = [];
    $configuration['blacklist'] = ['a', 'b'];
    $configuration['graylist'] = [];
    $configuration['module'] = ['module1' => 0, 'module2' => 0];
    $configuration['theme'] = ['theme1' => 0];
    $configuration['graylist_dependents'] = FALSE;

    // The config manager returns dependent entities for modules and themes.
    $manager = $this->prophesize('Drupal\Core\Config\ConfigManagerInterface');
    $manager->findConfigEntityDependencies(Argument::exact('module'), Argument::exact(['module1', 'module2']))
      ->willReturn(['c' => 0, 'd' => 0, 'a' => 0]);
    $manager->findConfigEntityDependencies(Argument::exact('theme'), Argument::exact(['theme1']))
      ->willReturn(['e' => 0, 'f' => 0, 'c' => 0]);
    // Add a config storage returning some settings for the filtered modules.
    $all_config = array_merge(array_fill_keys(range("a", "z"), []), ['module1.settings' => [], 'module3.settings' => []]);
    $manager->getConfigFactory()->willReturn($this->getConfigStorageStub($all_config));
    // Add more config dependencies, independently of what is asked for.
    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::cetera())
      ->willReturn(['f' => 0, 'g' => 0, 'b' => 0]);

    $filter = new SplitFilter($configuration, 'config_split', [], $manager->reveal());
    $actual = $filter->getBlacklist();
    // The order of values and keys are not important.
    sort($actual);
    $expected = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'module1.settings'];
    self::assertEqualsCanonicalizing($expected, $actual);
  }

  /**
   * Test that the graylist is correctly calculated.
   */
  public function testGraylist() {
    $configuration = [];
    $configuration['blacklist'] = [];
    $configuration['graylist'] = ['a', 'b'];
    $configuration['module'] = [];
    $configuration['theme'] = [];
    $configuration['graylist_dependents'] = TRUE;

    // The config manager returns dependent entities for modules and themes.
    $manager = $this->prophesize('Drupal\Core\Config\ConfigManagerInterface');
    $manager->findConfigEntityDependencies(Argument::exact('module'), Argument::cetera())->willReturn([]);
    $manager->findConfigEntityDependencies(Argument::exact('theme'), Argument::cetera())->willReturn([]);
    // Add a config storage returning some settings for the filtered modules.
    $all_config = array_merge(array_fill_keys(range("a", "z"), []), ['module1.settings' => [], 'module3.settings' => []]);
    $manager->getConfigFactory()->willReturn($this->getConfigStorageStub($all_config));
    // Add more config dependencies, independently of what is asked for.
    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::exact([]))->willReturn([]);
    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::exact(['a', 'b']))
      ->willReturn(['f' => 0, 'g' => 0, 'b' => 0]);

    $filter = new SplitFilter($configuration, 'config_split', [], $manager->reveal());
    $actual = $filter->getGraylist();
    // The order of values and keys are not important.
    sort($actual);
    self::assertEquals(['a', 'b', 'f', 'g'], $actual);
  }

  /**
   * Test that conditionally split config will not be completely split.
   */
  public function testConditionallySplitInCompleteSplit() {
    $configuration = [];
    $configuration['blacklist'] = ['b', 'c', 'd'];
    $configuration['graylist'] = ['a'];
    $configuration['module'] = [];
    $configuration['theme'] = [];
    $configuration['graylist_dependents'] = TRUE;

    // The config manager returns dependent entities for modules and themes.
    $manager = $this->prophesize('Drupal\Core\Config\ConfigManagerInterface');
    $manager->findConfigEntityDependencies(Argument::exact('module'), Argument::cetera())->willReturn([]);
    $manager->findConfigEntityDependencies(Argument::exact('theme'), Argument::cetera())->willReturn([]);
    // Add a config storage returning some settings for the filtered modules.
    $all_config = array_merge(array_fill_keys(range("a", "z"), []), ['module1.settings' => [], 'module3.settings' => []]);
    $manager->getConfigFactory()->willReturn($this->getConfigStorageStub($all_config));
    // Add more config dependencies, independently of what is asked for.
    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::exact(['b', 'c', 'd']))
      ->willReturn(['e' => 0]);
    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::exact(['a']))
      ->willReturn(['f' => 0, 'b' => 0]);

    $filter = new SplitFilter($configuration, 'config_split', [], $manager->reveal());
    $actual = $filter->getBlacklist();
    // The order of values and keys are not important.
    sort($actual);
    self::assertEquals(['c', 'd', 'e'], $actual);
  }

  /**
   * Test that the wildcards are properly taken into account.
   *
   * @dataProvider wildcardsData
   */
  public function testWildcards($name, $method) {

    $list = ['a', 'b', 'contact*', '*.d', 'e*i', 'f*de*', 'x'];

    $configuration = [];
    $configuration['blacklist'] = [];
    $configuration['graylist'] = [];
    $configuration['module'] = [];
    $configuration['theme'] = [];
    $configuration['graylist_dependents'] = TRUE;

    $configuration[$name] = $list;

    // The config manager returns dependent entities for modules and themes.
    $manager = $this->prophesize('Drupal\Core\Config\ConfigManagerInterface');
    $manager->findConfigEntityDependencies(Argument::exact('module'), Argument::cetera())->willReturn([]);
    $manager->findConfigEntityDependencies(Argument::exact('theme'), Argument::cetera())->willReturn([]);
    // Add a config storage returning some settings for the filtered modules.
    $all_config = array_merge(array_fill_keys(range("a", "z"), []), [
      'contact' => [],
      'contacts' => [],
      'contact.form' => [],
      'form' => [],
      'form.d' => [],
      'form.demo' => [],
      'formed' => [],
      'ei' => [],
      'efi' => [],
      'efghi' => [],
      'efghijk' => [],
      'abcdefghijk' => [],
    ]);
    $manager->getConfigFactory()->willReturn($this->getConfigStorageStub($all_config));

    $expected = [
      'a',
      'b',
      'contact',
      'contact.form',
      'contacts',
      'efghi',
      'efi',
      'ei',
      'form.d',
      'form.demo',
      'x',
    ];

    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::exact($expected))->willReturn([]);
    $manager->findConfigEntityDependencies(Argument::exact('config'), Argument::exact([]))->willReturn([]);

    $filter = new SplitFilter($configuration, 'config_split', [], $manager->reveal());
    $actual = $filter->{$method}();
    self::assertEquals($expected, $actual);
  }

  /**
   * The data for the wildcard test.
   */
  public function wildcardsData() {
    // We need to test the methods separately.
    return [
      ['blacklist', 'getBlacklist'],
      ['graylist', 'getGraylist'],
    ];
  }

  /**
   * Test that the filter reads correctly.
   */
  public function testFilterRead() {
    // Transparent filter.
    $name = $this->randomMachineName();
    $data = (array) $this->getRandomGenerator()->object();
    $filter = $this->getFilter();
    self::assertEquals($data, $filter->filterRead($name, $data));

    // Filter with a storage that has an alternative.
    $name2 = $this->randomMachineName();
    $data2 = (array) $this->getRandomGenerator()->object();
    $storage = $this->prophesize(StorageInterface::class);
    $storage->read($name)->willReturn(NULL);
    $storage->read($name2)->willReturn($data2);
    $filter = $this->getFilter($storage->reveal());
    self::assertEquals($data, $filter->filterRead($name, $data));
    self::assertEquals($data2, $filter->filterRead($name2, $data));

    // Test that extensions are correctly added.
    $extensions = [
      'module' => [
        'config' => 0,
        'user' => 0,
        'views_ui' => 0,
        'menu_link_content' => 1,
        'views' => 10,
      ],
      'theme' => ['stable' => 0, 'classy' => 0],
    ];
    $modules = [
      'module1' => 0,
      'module2' => 1,
    ];
    $themes = [
      'custom_theme' => 0,
    ];
    $extensions_extra = [
      'module' => [
        'config' => 0,
        'module1' => 0,
        'user' => 0,
        'views_ui' => 0,
        'menu_link_content' => 1,
        'module2' => 1,
        'views' => 10,
      ],
      'theme' => ['stable' => 0, 'classy' => 0, 'custom_theme' => 0],
    ];
    $filter = $this->getFilter(NULL, [], $modules, $themes);
    self::assertEquals($extensions_extra, $filter->filterRead('core.extension', $extensions));
    self::assertEquals($extensions_extra, $filter->filterRead('core.extension', $extensions_extra));

    // Test with reading from the wrapper storage.
    $filter = $this->getFilter(NULL, [], ['none' => 0], ['none' => 0], [], $name);
    $storage = $this->prophesize(StorageInterface::class);
    $storage->read($name)->willReturn(['module' => $modules, 'theme' => $themes]);
    $filter->setFilteredStorage($storage->reveal());
    self::assertEquals($extensions_extra, $filter->filterRead('core.extension', $extensions));
    self::assertEquals($extensions_extra, $filter->filterRead('core.extension', $extensions_extra));

    // Test with reading from the wrapper storage.
    $filter = $this->getFilter(NULL, [], ['none' => 0], ['none' => 0], [], $name);
    $storage = $this->prophesize(StorageInterface::class);
    $storage->read($name)->willReturn(FALSE);
    $filter->setFilteredStorage($storage->reveal());
    self::assertEquals($extensions, $filter->filterRead('core.extension', $extensions));
    self::assertEquals($extensions_extra, $filter->filterRead('core.extension', $extensions_extra));
  }

  /**
   * Test that the filter writes correctly.
   */
  public function testFilterWrite() {
    // Transparent filter.
    $name = $this->randomMachineName();
    $data = (array) $this->getRandomGenerator()->object();

    try {
      $filter = $this->getFilter();
      $filter->filterWrite($name, $data);
      $this->fail('The filter needs a storage.');
    }
    catch (\InvalidArgumentException $exception) {
      self::assertTrue(TRUE, 'Exception thrown.');
    }

    $filter = $this->getFilter(new NullStorage());
    self::assertEquals($data, $filter->filterWrite($name, $data));

    // Filter with a blacklist.
    $name2 = $this->randomMachineName();
    $filter = $this->getFilter(new NullStorage(), [$name2], [], []);
    self::assertEquals($data, $filter->filterWrite($name, $data));
    self::assertNull($filter->filterWrite($name2, $data));
    // Filter with a blacklist and a storage.
    $storage = $this->prophesize(StorageInterface::class);
    $storage->write(Argument::cetera())->willReturn(TRUE);
    $storage->exists($name)->willReturn(FALSE);
    $filter = $this->getFilter($storage->reveal(), [$name2], [], []);
    self::assertEquals($data, $filter->filterWrite($name, $data));
    self::assertNull($filter->filterWrite($name2, $data));

    // Filter with a gray list and a storage.
    $name3 = $this->randomMachineName();
    $data3 = (array) $this->getRandomGenerator()->object();
    $storage = $this->prophesize(StorageInterface::class);
    $storage->write(Argument::cetera())->willReturn(TRUE);
    $storage->read($name3)->willReturn($data3);
    $storage->exists($name)->willReturn(TRUE);
    $storage->delete($name)->willReturn(TRUE)->shouldBeCalled();
    $storage = $storage->reveal();
    $filter = $this->getFilter($storage, [$name2], [], [], [$name3]);
    $filter->setSourceStorage($storage);
    self::assertEquals($data, $filter->filterWrite($name, $data));
    self::assertNull($filter->filterWrite($name2, $data));
    self::assertEquals($data3, $filter->filterWrite($name3, $data));

    // Filter with graylist and skipping equal data.
    $primary = $this->prophesize(StorageInterface::class);
    $primary->read($name3)->willReturn($data3);
    $primary = $primary->reveal();
    $secondary = $this->prophesize(StorageInterface::class);
    $secondary->exists($name)->willReturn(FALSE);
    $secondary->write($name2, $data)->willReturn(TRUE);
    $secondary->write($name3, $data)->willReturn(TRUE);
    $secondary->exists($name3)->willReturn(TRUE);
    $secondary->delete($name3)->willReturn(TRUE)->shouldBeCalled();
    $secondary = $secondary->reveal();

    $filter = $this->getFilter($secondary, [$name2], [], [], [$name3], 'test', TRUE);
    $filter->setSourceStorage($primary);
    self::assertEquals($data, $filter->filterWrite($name, $data));
    self::assertNull($filter->filterWrite($name2, $data));
    self::assertEquals($data3, $filter->filterWrite($name3, $data));
    self::assertEquals($data3, $filter->filterWrite($name3, $data3));

    // Test that extensions are correctly removed.
    $extensions = [
      'module' => [
        'config' => 0,
        'user' => 0,
        'views_ui' => 0,
        'menu_link_content' => 1,
        'views' => 10,
      ],
      'theme' => ['stable' => 0, 'classy' => 0],
    ];
    $modules = [
      'module1' => 0,
      'module2' => 1,
    ];
    $themes = [
      'custom_theme' => 0,
    ];
    $extensions_extra = [
      'module' => [
        'config' => 0,
        'module1' => 0,
        'user' => 0,
        'views_ui' => 0,
        'menu_link_content' => 1,
        'module2' => 1,
        'views' => 10,
      ],
      'theme' => ['stable' => 0, 'classy' => 0, 'custom_theme' => 0],
    ];
    $filter = $this->getFilter(new NullStorage(), [], $modules, $themes);
    self::assertEquals($extensions, $filter->filterWrite('core.extension', $extensions));
    self::assertEquals($extensions, $filter->filterWrite('core.extension', $extensions_extra));

    // Test that empty config is not written to the split storage.
    $storage = $this->prophesize(StorageInterface::class);
    $storage->write($name2, [])->shouldNotBeCalled();
    $storage->write($name3, [])->shouldNotBeCalled();
    $filter = $this->getFilter($storage->reveal(), [$name2], [], [], [$name3], 'test', TRUE);
    self::assertNull($filter->filterWrite($name2, []));
    self::assertNull($filter->filterWrite($name3, []));
  }

  /**
   * Test that the filter checks existence correctly.
   */
  public function testFilterExists() {
    $storage = $this->prophesize(StorageInterface::class);
    $storage->exists('Yes')->willReturn(TRUE);
    $storage->exists('No')->willReturn(FALSE);

    $transparent = $this->getFilter(NULL);
    $filter = $this->getFilter($storage->reveal());

    self::assertTrue($transparent->filterExists('Yes', TRUE));
    self::assertTrue($transparent->filterExists('No', TRUE));
    self::assertFalse($transparent->filterExists('Yes', FALSE));
    self::assertFalse($transparent->filterExists('No', FALSE));

    self::assertTrue($filter->filterExists('Yes', TRUE));
    self::assertTrue($filter->filterExists('No', TRUE));
    self::assertTrue($filter->filterExists('Yes', FALSE));
    self::assertFalse($filter->filterExists('No', FALSE));
  }

  /**
   * Test that the filter deletes correctly.
   */
  public function testFilterDelete() {
    $storage = $this->prophesize(StorageInterface::class);
    $storage->exists('Yes')->willReturn(TRUE);
    $storage->delete('Yes')->willReturn(TRUE);

    $transparent = $this->getFilter(NULL);
    $filter = $this->getFilter($storage->reveal());

    self::assertTrue($transparent->filterDelete('Yes', TRUE));
    self::assertFalse($transparent->filterDelete('No', FALSE));

    self::assertTrue($filter->filterDelete('Yes', TRUE));
    self::assertFalse($filter->filterDelete('No', FALSE));
  }

  /**
   * Test that the filter reads multiple objects correctly.
   */
  public function testFilterReadMultiple() {
    // Set up random config storage.
    $primary = (array) $this->getRandomGenerator()->object(rand(3, 10));
    $secondary = (array) $this->getRandomGenerator()->object(rand(3, 10));
    $merged = array_merge($primary, $secondary);
    $storage = $this->prophesize(StorageInterface::class);
    $storage->readMultiple(Argument::cetera())->willReturn($secondary);

    $transparent = $this->getFilter(NULL);
    $filter = $this->getFilter($storage->reveal());

    // Test listing config.
    self::assertEquals($primary, $transparent->filterReadMultiple(array_keys($merged), $primary));
    self::assertEquals($merged, $filter->filterReadMultiple(array_keys($merged), $primary));
  }

  /**
   * Test that the filter lists all correctly.
   */
  public function testFilterListAll() {
    // Set up random config storage.
    $primary = (array) $this->getRandomGenerator()->object(rand(3, 10));
    $secondary = (array) $this->getRandomGenerator()->object(rand(3, 10));
    $merged = array_merge($primary, $secondary);
    $storage = $this->getConfigStorageStub($secondary);

    $transparent = $this->getFilter(NULL);
    $filter = $this->getFilter($storage);

    // Test listing config.
    self::assertEquals(array_keys($primary), $transparent->filterListAll('', array_keys($primary)));
    self::assertEquals(array_keys($merged), $filter->filterListAll('', array_keys($primary)));
  }

  /**
   * Test that the filter deletes all correctly.
   */
  public function testFilterDeleteAll() {
    $storage = $this->prophesize(StorageInterface::class);
    $storage->deleteAll('Yes')->willReturn(TRUE);

    $transparent = $this->getFilter(NULL);
    $filter = $this->getFilter($storage->reveal());

    self::assertTrue($transparent->filterDeleteAll('Yes', TRUE));
    self::assertFalse($transparent->filterDeleteAll('No', FALSE));

    self::assertTrue($filter->filterDeleteAll('Yes', TRUE));
    self::assertFalse($filter->filterDeleteAll('No', FALSE));

    // Test that the storage can throw an exception without affecting execution.
    $failing = $this->prophesize(StorageInterface::class);
    $failing->deleteAll('Yes')->willThrow('\UnexpectedValueException');

    $filter = $this->getFilter($failing->reveal());
    self::assertTrue($filter->filterDeleteAll('Yes', TRUE));
  }

  /**
   * Test that the filter creates collections correctly.
   */
  public function testFilterCreateCollection() {
    $collection = $this->randomMachineName();
    $collection_storage = new NullStorage();
    $storage = $this->prophesize(StorageInterface::class);
    $storage->createCollection($collection)->willReturn($collection_storage);

    $transparent = $this->getFilter(NULL);
    self::assertEquals($transparent, $transparent->filterCreateCollection($collection));

    $filter = $this->getFilter($storage->reveal());
    $new_filter = $filter->filterCreateCollection($collection);

    // Get the protected storage property.
    $internal = new \ReflectionProperty(SplitFilter::class, 'secondaryStorage');
    $internal->setAccessible(TRUE);
    $actual = $internal->getValue($new_filter);
    self::assertEquals($collection_storage, $actual);
  }

  /**
   * Test that the filter gets collections names correctly.
   */
  public function testFilterGetAllCollectionNames() {
    $collections = array_keys((array) $this->getRandomGenerator()->object(rand(3, 10)));
    $extra = array_keys((array) $this->getRandomGenerator()->object(rand(3, 10)));
    $storage = $this->prophesize(StorageInterface::class);
    $storage->getAllCollectionNames()->willReturn($extra);

    $transparent = $this->getFilter(NULL);
    $filter = $this->getFilter($storage->reveal());

    self::assertEquals($collections, $transparent->filterGetAllCollectionNames($collections));
    self::assertEquals(array_merge($collections, $extra), $filter->filterGetAllCollectionNames($collections));
  }

  /**
   * Test that the static create method works and folders contain the htaccess.
   */
  public function testSplitFilterCreate() {
    $name = 'config_split.' . $this->getRandomGenerator()->name();
    // Set the split stream up.
    $folder = vfsStream::setup($name);
    $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->get('config.manager')->willReturn($this->getConfigManagerMock());
    $container->get('config.factory')->willReturn($this->getConfigFactoryStub([
      $name => [
        'folder' => $folder->url(),
        'module' => [],
        'theme' => [],
        'blacklist' => [],
        'graylist' => [],
      ],
    ]));
    $database = $this->prophesize('Drupal\Core\Database\Connection');
    $container->get('database')->willReturn($database->reveal());

    $configuration = [
      'config_name' => $name,
    ];

    $filter = SplitFilter::create($container->reveal(), $configuration, $this->getRandomGenerator()->name(), []);
    self::assertTrue($folder->hasChild('.htaccess'), 'htaccess written to split folder.');

    $folder->addChild(new vfsStreamFile($name . '.' . FileStorage::getFileExtension()));
    self::assertTrue($filter->filterExists($name, FALSE), 'Assert filename');

    // Test split with db storage.
    $name = 'config_split.' . $this->getRandomGenerator()->name();
    $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->get('config.manager')->willReturn($this->getConfigManagerMock());
    $container->get('config.factory')->willReturn($this->getConfigFactoryStub([
      $name => [
        'folder' => '',
        'module' => [],
        'theme' => [],
        'blacklist' => [],
        'graylist' => [],
      ],
    ]));
    $database = $this->prophesize('Drupal\Core\Database\Connection')->reveal();
    $container->get('database')->willReturn($database);

    $configuration = [
      'config_name' => $name,
    ];

    $filter = SplitFilter::create($container->reveal(), $configuration, $this->getRandomGenerator()->name(), []);
    // Get the protected secondaryStorage property.
    $storage = new \ReflectionProperty(SplitFilter::class, 'secondaryStorage');
    $storage->setAccessible(TRUE);
    $secondary = $storage->getValue($filter);

    self::assertInstanceOf(DatabaseStorage::class, $secondary);
  }

  /**
   * Returns a SplitFilter that can be used to test its behaviour.
   *
   * @param \Drupal\Core\Config\StorageInterface|null $storage
   *   The Storage interface the filter can use as its alternative storage.
   * @param string[] $blacklist
   *   The blacklisted configuration that is filtered out.
   * @param array $modules
   *   The blacklisted modules that are removed from the core.extensions.
   * @param array $themes
   *   The blacklisted themes that are removed from the core.extensions.
   * @param string[] $graylist
   *   The graylisted configuration that is filtered out.
   * @param string $name
   *   The name of the prophesied config object.
   * @param bool $skip_equal
   *   The flag to skip equal config in graylist exports.
   *
   * @return \Drupal\config_split\Plugin\ConfigFilter\SplitFilter
   *   The filter to test.
   */
  protected function getFilter(StorageInterface $storage = NULL, array $blacklist = [], array $modules = [], array $themes = [], array $graylist = [], $name = 'config_split.config_split.test', $skip_equal = FALSE) {
    $configuration = [];
    $configuration['blacklist'] = $blacklist;
    $configuration['graylist'] = $graylist;
    $configuration['graylist_dependents'] = TRUE;
    $configuration['graylist_skip_equal'] = $skip_equal;
    $configuration['module'] = $modules;
    $configuration['theme'] = $themes;
    $configuration['config_name'] = $name;

    // Return a new filter that behaves as intended.
    return new SplitFilter($configuration, 'config_split', [], $this->getConfigManagerMock($blacklist, $graylist, $modules, $themes), $storage);
  }

  /**
   * Gets a mocked version of the config manager.
   *
   * @param array $blacklist
   *   The config names to blacklist.
   * @param array $graylist
   *   The config names to graylist.
   * @param array $modules
   *   The array of modules.
   * @param array $themes
   *   The array of themes.
   *
   * @return \Drupal\Core\Config\ConfigManagerInterface
   *   The mocked config manager.
   */
  protected function getConfigManagerMock(array $blacklist = [], array $graylist = [], array $modules = [], array $themes = []) {
    // The manager returns nothing but allows the filter to set up correctly.
    // This means that the blacklist is not enhanced but only the one passed
    // as an argument is used.
    $manager = $this->prophesize('Drupal\Core\Config\ConfigManagerInterface');
    $manager->findConfigEntityDependencies(Argument::cetera())->willReturn([]);
    // The config factory should return config names for at least all
    // blacklisted and gray listed configuration.
    $all_config = array_fill_keys(array_merge($blacklist, $graylist, array_keys($modules), array_keys($themes)), []);
    $manager->getConfigFactory()->willReturn($this->getConfigStorageStub($all_config));
    return $manager->reveal();
  }

}
