<?php

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\ComponentsRegistry;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\components\Template\ComponentsRegistry
 * @group components
 */
class ComponentsRegistryTest extends UnitTestCase {

  /**
   * The logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerChannel;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\ComponentsRegistry
   */
  protected $systemUnderTest;

  /**
   * Path to the mocked modules directory.
   *
   * @var string
   */
  protected $modulesDir = 'modules/contrib';

  /**
   * Path to the mocked themes directory.
   *
   * @var string
   */
  protected $themesDir = 'themes/contrib';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    // Mock LoggerChannelTrait.
    $this->loggerChannel = $this->createMock('\Drupal\Core\Logger\LoggerChannel');
    $loggerFactory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactory');
    $loggerFactory->method('get')->willReturn($this->loggerChannel);
    $container->set('logger.factory', $loggerFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Invokes a protected or private method of an object.
   *
   * @param object|null $obj
   *   The instantiated object (or null for static methods.)
   * @param string $method
   *   The method to invoke.
   * @param mixed $args
   *   The parameters to be passed to the method.
   *
   * @return mixed
   *   The return value of the method.
   *
   * @throws \ReflectionException
   */
  public function invokeProtectedMethod(?object $obj, string $method, ...$args) {
    // Use reflection to test a protected method.
    $methodUnderTest = new \ReflectionMethod($obj, $method);
    $methodUnderTest->setAccessible(TRUE);
    return $methodUnderTest->invokeArgs($obj, $args);
  }

  /**
   * Gets the value of a protected or private property of an object.
   *
   * @param object $obj
   *   The instantiated object (or null for static methods.)
   * @param string $property
   *   The property to get.
   *
   * @return mixed
   *   The value of the property.
   *
   * @throws \ReflectionException
   */
  public function getProtectedProperty(object $obj, string $property) {
    $propertyUnderTest = new \ReflectionProperty($obj, $property);
    $propertyUnderTest->setAccessible(TRUE);
    return $propertyUnderTest->getValue($obj);
  }

  /**
   * Sets the value of a protected or private property of an object.
   *
   * @param object $obj
   *   The instantiated object (or null for static methods.)
   * @param string $property
   *   The property to set.
   * @param mixed $value
   *   The value of the property.
   *
   * @throws \ReflectionException
   */
  public function setProtectedProperty(object $obj, string $property, $value): void {
    $propertyUnderTest = new \ReflectionProperty($obj, $property);
    $propertyUnderTest->setAccessible(TRUE);
    $propertyUnderTest->setValue($obj, $value);
  }

  /**
   * Creates a ComponentsRegistry service after the dependencies are set up.
   *
   * @param null|\Drupal\Core\Extension\ModuleExtensionList|\PHPUnit\Framework\MockObject\MockObject $moduleExtensionList
   *   The mocked module extension list service.
   * @param null|\Drupal\Core\Extension\ThemeExtensionList|\PHPUnit\Framework\MockObject\MockObject $themeExtensionList
   *   The mocked theme extension list service.
   * @param null|\Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject $moduleHandler
   *   The mocked module handler service.
   * @param null|\Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $themeManager
   *   The mocked theme manager service.
   * @param null|\Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject $cacheBackend
   *   The mocked caching service.
   * @param null|\Drupal\Core\File\FileSystemInterface|\PHPUnit\Framework\MockObject\MockObject $fileSystem
   *   The mocked file system service.
   *
   * @return \Drupal\components\Template\ComponentsRegistry
   *   A new ComponentsRegistry object.
   */
  public function newSystemUnderTest(
    $moduleExtensionList = NULL,
    $themeExtensionList = NULL,
    $moduleHandler = NULL,
    $themeManager = NULL,
    $cacheBackend = NULL,
    $fileSystem = NULL
  ): ComponentsRegistry {
    // Generate mock objects with the minimum mocking to run the constructor.
    if (is_null($moduleExtensionList)) {
      $moduleExtensionList = $this->createMock('\Drupal\Core\Extension\ModuleExtensionList');
      $moduleExtensionList->method('getAllInstalledInfo')->willReturn([]);
    }
    if (is_null($themeExtensionList)) {
      $themeExtensionList = $this->createMock('\Drupal\Core\Extension\ThemeExtensionList');
      $themeExtensionList->method('getAllInstalledInfo')->willReturn([]);
      $themeExtensionList->method('getList')->willReturn([]);
      $themeExtensionList->method('getBaseThemes')->willReturn([]);
    }
    if (is_null($moduleHandler)) {
      $moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    }
    if (is_null($themeManager)) {
      $themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    }
    if (is_null($cacheBackend)) {
      $cacheBackend = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    }
    if (is_null($fileSystem)) {
      $fileSystem = $this->createMock('\Drupal\Core\File\FileSystemInterface');
      $fileSystem->method('scanDirectory')->willReturn([]);
    }

    return new ComponentsRegistry(
      $moduleExtensionList,
      $themeExtensionList,
      $moduleHandler,
      $themeManager,
      $cacheBackend,
      $fileSystem
    );
  }

  /**
   * Tests getting a template from the components registry.
   *
   * @throws \ReflectionException
   *
   * @covers ::getTemplate
   *
   * @dataProvider providerTestGetTemplate
   */
  public function testGetTemplate(string $name, string $activeTheme, array $registry, bool $needsLoad, ?string $expected): void {
    $cacheBackend = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    if ($needsLoad) {
      $cacheBackend
        ->method('get')
        ->willReturnOnConsecutiveCalls((object) ['data' => $registry]);
    }

    $themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $activeThemeObject = $this->createMock('\Drupal\Core\Theme\ActiveTheme');
    $activeThemeObject
      ->method('getName')
      ->willReturn($activeTheme);
    $themeManager
      ->method('getActiveTheme')
      ->willReturn($activeThemeObject);

    $this->systemUnderTest = $this->newSystemUnderTest(NULL, NULL, NULL, $themeManager, $cacheBackend, NULL);

    if (!$needsLoad) {
      $this->setProtectedProperty($this->systemUnderTest, 'registry', [$activeTheme => $registry]);
    }

    $result = $this->systemUnderTest->getTemplate($name);
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Provides test data to ::testGetTemplate().
   *
   * @see testGetTemplate()
   */
  public function providerTestGetTemplate(): array {
    return [
      'gets the template from registry' => [
        'name' => '@components/tubman.twig',
        'activeTheme' => 'activeTheme',
        'registry' => [
          '@components/tubman.twig' => 'themes/activeTheme/components/tubman.twig',
        ],
        'needsLoad' => TRUE,
        'expected' => 'themes/activeTheme/components/tubman.twig',
      ],
      'gets the template from pre-loaded registry' => [
        'name' => '@components/tubman.twig',
        'activeTheme' => 'activeTheme',
        'registry' => [
          '@components/tubman.twig' => 'themes/activeTheme/components/tubman.twig',
        ],
        'needsLoad' => FALSE,
        'expected' => 'themes/activeTheme/components/tubman.twig',
      ],
      'returns NULL when template is not found' => [
        'name' => '@components/bob/tubman.twig',
        'activeTheme' => 'activeTheme',
        'registry' => [
          '@components/tubman.twig' => 'themes/activeTheme/components/tubman.twig',
        ],
        'needsLoad' => TRUE,
        'expected' => NULL,
      ],
    ];
  }

  /**
   * Tests loading the components registry.
   *
   * @param string $themeName
   *   The name of the active theme.
   * @param array $namespaces
   *   The cache of the active theme's namespaces.
   * @param string|array $scanDirectory
   *   An array of file paths to return for $fileSystem::scanDirectory(), keyed
   *   by the directory path. If a string is given, the mock will throw an
   *   exception.
   * @param array|null $cache
   *   The components:registry:[themeName] cache.
   * @param bool $isLoaded
   *   The return value of moduleHandler->isLoaded().
   * @param array $expected
   *   The expected result.
   * @param array $expectedWarnings
   *   The list of expected warnings.
   *
   * @throws \ReflectionException
   *
   * @covers ::load
   *
   * @dataProvider providerTestLoad
   */
  public function testLoad(string $themeName, array $namespaces, $scanDirectory, ?array $cache, bool $isLoaded, array $expected, array $expectedWarnings = []): void {

    $moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandler
      ->expects($this->exactly(empty($cache) ? 1 : 0))
      ->method('isLoaded')
      ->willReturn($isLoaded);

    $cacheBackend = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $cacheBackend
      ->method('get')
      ->willReturnOnConsecutiveCalls(
        !empty($cache) ? (object) ['data' => $cache] : FALSE,
        !empty($namespaces) ? (object) ['data' => $namespaces] : FALSE,
      );

    if (!empty($expectedWarnings)) {
      $consecutiveCalls = [];
      foreach ($expectedWarnings as $key => $warning) {
        $consecutiveCalls[$key] = [$warning];
      }
      $this->loggerChannel
        ->expects($this->exactly(count($expectedWarnings)))
        ->method('warning')
        ->withConsecutive(...$consecutiveCalls);
    }

    $fileSystem = $this->createMock('\Drupal\Core\File\FileSystemInterface');
    if (is_string($scanDirectory)) {
      $fileSystem
        ->method('scanDirectory')
        ->will($this->throwException(new NotRegularDirectoryException("$scanDirectory is not a directory.")));
    }
    else {
      $valueMap = [];
      foreach ($scanDirectory as $dir => $paths) {
        $returnVal = [];
        foreach ($paths as $uri) {
          $file = new \stdClass();
          $file->uri = $uri;
          $file->filename = basename($uri);
          $file->name = pathinfo($uri, PATHINFO_FILENAME);
          $returnVal[$uri] = $file;
        }
        $valueMap[] = [$dir, '/\.(twig|html|svg)$/', [], $returnVal];
      }
      $fileSystem
        ->expects($this->exactly(count($scanDirectory)))
        ->method('scanDirectory')
        ->will($this->returnValueMap($valueMap));
    }

    $this->systemUnderTest = $this->newSystemUnderTest(NULL, NULL, $moduleHandler, NULL, $cacheBackend, $fileSystem);

    // Use reflection to test a protected method.
    $this->invokeProtectedMethod($this->systemUnderTest, 'load', $themeName);
    $result = $this->getProtectedProperty($this->systemUnderTest, 'registry')[$themeName];
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Provides test data to ::testLoad().
   *
   * @see testLoad()
   */
  public function providerTestLoad(): array {
    return [
      'loads registry from cache' => [
        'themeName' => 'activeTheme',
        'namespaces' => [],
        'scanDirectory' => [],
        'cache' => [
          '@components/tubman.twig' => 'components/harriet/tubman.twig',
          '@components/harriet/tubman.twig' => 'components/harriet/tubman.twig',
        ],
        'isLoaded' => FALSE,
        'expected' => [
          '@components/tubman.twig' => 'components/harriet/tubman.twig',
          '@components/harriet/tubman.twig' => 'components/harriet/tubman.twig',
        ],
        'expectedWarnings' => [],
      ],
      'registers full path and short path to a template' => [
        'themeName' => 'activeTheme',
        'namespaces' => [
          'components' => [
            'themes/activeTheme/components',
          ],
        ],
        'scanDirectory' => [
          'themes/activeTheme/components' =>
            ['themes/activeTheme/components/harriet/tubman.twig'],
        ],
        'cache' => [],
        'isLoaded' => FALSE,
        'expected' => [
          '@components/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
          '@components/harriet/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
        ],
        'expectedWarnings' => [],
      ],
      'logs a warning for non-existent paths' => [
        'themeName' => 'activeTheme',
        'namespaces' => [
          'components' => [
            'themes/activeTheme/components',
          ],
        ],
        'scanDirectory' => 'themes/activeTheme/components',
        'cache' => [],
        'isLoaded' => FALSE,
        'expected' => [],
        'expectedWarnings' => [
          'The "@components" namespace contains a path, "themes/activeTheme/components", that is not a directory.',
        ],
      ],
      'logs a warning for duplicate templates' => [
        'themeName' => 'activeTheme',
        'namespaces' => [
          'components' => [
            'themes/activeTheme/components',
          ],
        ],
        'scanDirectory' => [
          'themes/activeTheme/components' => [
            'themes/activeTheme/components/harriet/tubman.twig',
            'themes/activeTheme/components/robert/tubman.twig',
          ],
        ],
        'cache' => [],
        'isLoaded' => FALSE,
        'expected' => [
          '@components/harriet/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
          '@components/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
          '@components/robert/tubman.twig' => 'themes/activeTheme/components/robert/tubman.twig',
        ],
        'expectedWarnings' => [
          'Found multiple files for the "@components/tubman.twig" template; it is recommended to only have one "tubman.twig" file in the "components" namespace’s "themes/activeTheme/components" directory. Found: themes/activeTheme/components/harriet/tubman.twig, themes/activeTheme/components/robert/tubman.twig',
        ],
      ],
      'sorts namespace paths per directory' => [
        'themeName' => 'activeTheme',
        'namespaces' => [
          'components' => [
            'themes/activeTheme/components',
          ],
        ],
        'scanDirectory' => [
          'themes/activeTheme/components' => [
            'themes/activeTheme/components/robert/tubman.twig',
            'themes/activeTheme/components/harriet/tubman.twig',
            'themes/activeTheme/components/bob/tubman.twig',
          ],
        ],
        'cache' => [],
        'isLoaded' => FALSE,
        'expected' => [
          '@components/bob/tubman.twig' => 'themes/activeTheme/components/bob/tubman.twig',
          '@components/tubman.twig' => 'themes/activeTheme/components/bob/tubman.twig',
          '@components/harriet/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
          '@components/robert/tubman.twig' => 'themes/activeTheme/components/robert/tubman.twig',
        ],
        'expectedWarnings' => [
          'Found multiple files for the "@components/tubman.twig" template; it is recommended to only have one "tubman.twig" file in the "components" namespace’s "themes/activeTheme/components" directory. Found: themes/activeTheme/components/bob/tubman.twig, themes/activeTheme/components/harriet/tubman.twig, themes/activeTheme/components/robert/tubman.twig',
        ],
      ],
      'saves the template registry' => [
        'themeName' => 'activeTheme',
        'namespaces' => [
          'components' => [
            'themes/activeTheme/components',
          ],
        ],
        'scanDirectory' => [
          'themes/activeTheme/components' =>
            ['themes/activeTheme/components/harriet/tubman.twig'],
        ],
        'cache' => [],
        'isLoaded' => TRUE,
        'expected' => [
          '@components/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
          '@components/harriet/tubman.twig' => 'themes/activeTheme/components/harriet/tubman.twig',
        ],
        'expectedWarnings' => [],
      ],
    ];
  }

  /**
   * Tests getting namespaces for the active theme.
   *
   * @param string $themeName
   *   The name of the active theme.
   * @param array $themeInfo
   *   The array returned by themeExtensionList::getAllInstalledInfo().
   * @param array $getPath
   *   The PHPUnit returnValueMap array for extensionList::getPath().
   * @param array $themeCache
   *   The $cache->get->data value for 'components:namespaces:[activeTheme]'.
   * @param array $allNamespacesCache
   *   The $cache->get->data value for 'components:namespaces'.
   * @param array $expected
   *   The expected result.
   *
   * @throws \ReflectionException
   *
   * @covers ::getNamespaces
   *
   * @dataProvider providerTestGetNamespaces
   */
  public function testGetNamespaces(string $themeName, array $themeInfo, array $getPath, array $themeCache, array $allNamespacesCache, array $expected): void {
    $moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandler
      ->method('isLoaded')
      ->willReturn(TRUE);
    $themeExtensionList = $this->createMock('\Drupal\Core\Extension\ThemeExtensionList');
    $themeExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn($themeInfo);
    $themeExtensionList
      ->method('getPath')
      ->will($this->returnValueMap($getPath));
    $themeList = [];
    foreach ($themeInfo as $info) {
      $extension = $this->createMock('\Drupal\Core\Extension\Extension');
      $extension->method('getName')->willReturn($info['name']);
      $extension->method('getType')->willReturn('theme');
      $themeList[] = $extension;
    }
    $valueMap = [];
    foreach ($themeInfo as $key => $theme) {
      $valueMap[] = [
        $themeList,
        $key,
        !isset($theme['base theme']) ? [] : [
          $theme['base theme'] => $themeInfo[$theme['base theme']]['name'],
        ],
      ];
    }
    $themeExtensionList
      ->method('getList')
      ->willReturn($themeList);
    $themeExtensionList
      ->method('getBaseThemes')
      ->will($this->returnValueMap($valueMap));

    $activeThemes = [];
    foreach (array_keys($themeInfo) as $activeThemeName) {
      $activeTheme = $this->createMock('\Drupal\Core\Theme\ActiveTheme');
      $activeTheme
        ->method('getName')
        ->willReturn($activeThemeName);
      $activeThemes[] = $activeTheme;
    }
    $themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    if (!empty($activeThemes)) {
      $themeManager
        ->method('getActiveTheme')
        ->willReturn(...$activeThemes);
    }

    $cacheBackend = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $cacheBackend
      ->method('get')
      ->willReturnOnConsecutiveCalls(
        !empty($themeCache) ? (object) ['data' => $themeCache] : FALSE,
        !empty($allNamespacesCache) ? (object) ['data' => $allNamespacesCache] : FALSE,
      );
    $set = [];
    $alter = [];
    if (empty($allNamespacesCache)) {
      $set[] = [
        'components:namespaces',
        $expected,
        Cache::PERMANENT,
        ['theme_registry'],
      ];
      $protected = [];
      foreach ($themeInfo as $key => $value) {
        $protected[$key] = [
          'name' => $value['name'],
          'type' => $value['type'],
          'package' => $value['package'] ?? '',
        ];
      }
      $alter[] = [
        'protected_twig_namespaces',
        $protected,
      ];
    }
    if (empty($themeCache)) {
      $set[] = [
        'components:namespaces:' . $themeName,
        $expected[$themeName],
        Cache::PERMANENT,
        ['theme_registry'],
      ];
      if (!empty($allNamespacesCache)) {
        $alter[] = [
          'components_namespaces',
          $allNamespacesCache[$themeName],
          $themeName,
        ];
      }
    }
    if (!empty($set)) {
      $cacheBackend
        ->method('set')
        ->withConsecutive(...$set);
    }
    if (!empty($alter)) {
      foreach ([$moduleHandler, $themeManager] as &$extensionHandler) {
        $extensionHandler
          ->method('alter')
          ->withConsecutive(...$alter);
      }
    }

    $this->systemUnderTest = $this->newSystemUnderTest(NULL, $themeExtensionList, $moduleHandler, $themeManager, $cacheBackend);

    $result = $this->systemUnderTest->getNamespaces($themeName);
    $this->assertEquals($expected[$themeName], $result, $this->getName());
  }

  /**
   * Provides test data to ::testGetNamespaces().
   *
   * @see testGetNamespaces()
   */
  public function providerTestGetNamespaces(): array {
    return [
      'gets namespaces from extension list' => [
        'themeName' => 'activeTheme',
        'themeInfo' => [
          'activeTheme' => [
            'name' => 'Active theme',
            'type' => 'theme',
            'base theme' => 'baseTheme',
            'components' => [
              'namespaces' => [
                'components' => ['path1', 'path2'],
              ],
            ],
          ],
          'baseTheme' => [
            'name' => 'Base theme',
            'type' => 'theme',
            'components' => [
              'namespaces' => [
                'components' => ['path3'],
              ],
            ],
          ],
        ],
        'getPath' => [
          ['activeTheme', $this->themesDir . '/activeTheme'],
          ['baseTheme', $this->themesDir . '/baseTheme'],
        ],
        'themeCache' => [],
        'allNamespacesCache' => [],
        'expected' => [
          'activeTheme' => [
            'components' => [
              $this->themesDir . '/activeTheme/path1',
              $this->themesDir . '/activeTheme/path2',
              $this->themesDir . '/baseTheme/path3',
            ],
          ],
          'baseTheme' => [
            'components' => [
              $this->themesDir . '/baseTheme/path3',
            ],
          ],
        ],
      ],
      'gets allNamespaces from cache' => [
        'themeName' => 'activeTheme',
        'themeInfo' => [],
        'getPath' => [],
        'themeCache' => [],
        'allNamespacesCache' => [
          'activeTheme' => [
            'components' => [
              $this->themesDir . '/activeTheme/path1',
              $this->themesDir . '/activeTheme/path2',
              $this->themesDir . '/baseTheme/path1',
            ],
          ],
          'baseTheme' => [
            'components' => [
              $this->themesDir . '/baseTheme/path1',
            ],
          ],
        ],
        'expected' => [
          'activeTheme' => [
            'components' => [
              $this->themesDir . '/activeTheme/path1',
              $this->themesDir . '/activeTheme/path2',
              $this->themesDir . '/baseTheme/path1',
            ],
          ],
          'baseTheme' => [
            'components' => [
              $this->themesDir . '/baseTheme/path1',
            ],
          ],
        ],
      ],
      'gets namespaces from cache' => [
        'themeName' => 'activeTheme',
        'themeInfo' => [],
        'getPath' => [],
        'themeCache' => [
          'components' => [
            $this->themesDir . '/activeTheme/path1',
            $this->themesDir . '/activeTheme/path2',
          ],
        ],
        'allNamespacesCache' => [],
        'expected' => [
          'activeTheme' => [
            'components' => [
              $this->themesDir . '/activeTheme/path1',
              $this->themesDir . '/activeTheme/path2',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests finding all the namespaces for every installed theme.
   *
   * @param array $moduleInfo
   *   The array returned by moduleExtensionList::getAllInstalledInfo().
   * @param array $themeInfo
   *   The array returned by themeExtensionList::getAllInstalledInfo().
   * @param array $getPath
   *   The PHPUnit returnValueMap array for extensionList::getPath().
   * @param array $getBaseThemes
   *   A theme-name-keyed array of return values for
   *   themeExtensionList::getBaseThemes().
   * @param array $expected
   *   The expected result.
   * @param array $expectedWarnings
   *   The list of expected warnings.
   *
   * @throws \ReflectionException
   *
   * @covers ::findNamespaces
   *
   * @dataProvider providerTestFindNamespaces
   */
  public function testFindNamespaces(array $moduleInfo, array $themeInfo, array $getPath, array $getBaseThemes, array $expected, array $expectedWarnings = []) {
    // Mock the method params with the test data.
    $moduleExtensionList = $this->createMock('\Drupal\Core\Extension\ModuleExtensionList');
    $themeExtensionList = $this->createMock('\Drupal\Core\Extension\ThemeExtensionList');
    $moduleExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn($moduleInfo);
    $themeExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn($themeInfo);
    if (!empty($getPath)) {
      $moduleExtensionList
        ->method('getPath')
        ->will($this->returnValueMap($getPath));
      $themeExtensionList
        ->method('getPath')
        ->will($this->returnValueMap($getPath));
    }
    $themeList = [];
    foreach ($themeInfo as $info) {
      $extension = $this->createMock('\Drupal\Core\Extension\Extension');
      $extension->method('getName')->willReturn($info['name']);
      $extension->method('getType')->willReturn('theme');
      $themeList[] = $extension;
    }
    $valueMap = [];
    foreach (array_keys($themeInfo) as $themeName) {
      $valueMap[] = [$themeList, $themeName, $getBaseThemes[$themeName]];
    }
    $themeExtensionList
      ->method('getList')
      ->willReturn($themeList);
    $themeExtensionList
      ->method('getBaseThemes')
      ->will($this->returnValueMap($valueMap));
    if (!empty($expectedWarnings)) {
      $consecutiveCalls = [];
      foreach ($expectedWarnings as $key => $warning) {
        $consecutiveCalls[$key] = [$warning];
      }
      $this->loggerChannel
        ->expects($this->exactly(count($expectedWarnings)))
        ->method('warning')
        ->withConsecutive(...$consecutiveCalls);
    }

    $this->systemUnderTest = $this->newSystemUnderTest();

    // Use reflection to test a protected method.
    $result = $this->invokeProtectedMethod($this->systemUnderTest, 'findNamespaces', $moduleExtensionList, $themeExtensionList);
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Provides test data to ::testFindNamespaces().
   *
   * @see testFindNamespaces()
   */
  public function providerTestFindNamespaces(): array {
    return [
      'namespace paths are ordered properly' => [
        'moduleInfo' => [
          'weight1' => [
            'name' => 'Weight 1',
            'type' => 'module',
            'components' => [
              'namespaces' => [
                'components' => ['path1', 'path2'],
                'baseTheme' => ['path3', 'path4'],
              ],
            ],
          ],
          'weight2' => [
            'name' => 'Weight 2',
            'type' => 'module',
            'components' => [
              'namespaces' => [
                'components' => ['path1', 'path2'],
                'baseTheme' => ['path3', 'path4'],
              ],
            ],
          ],
          'components' => [
            'name' => 'Components!',
            'type' => 'module',
            'components' => [
              'namespaces' => [
                'components' => ['path1', 'path2'],
              ],
            ],
          ],
        ],
        'themeInfo' => [
          'activeTheme' => [
            'name' => 'Active theme',
            'type' => 'theme',
            'base theme' => 'baseTheme',
            'components' => [
              'namespaces' => [
                'components' => ['path1', 'path2'],
              ],
            ],
          ],
          'baseTheme' => [
            'name' => 'Base theme',
            'type' => 'theme',
            'components' => [
              'namespaces' => [
                'components' => ['path1', 'path2'],
                'baseTheme' => ['path3', 'path4'],
              ],
            ],
          ],
        ],
        'getPath' => [
          ['components', $this->modulesDir . '/components'],
          ['weight1', $this->modulesDir . '/weight1'],
          ['weight2', $this->modulesDir . '/weight2'],
          ['activeTheme', $this->themesDir . '/activeTheme'],
          ['baseTheme', $this->themesDir . '/baseTheme'],
        ],
        'getBaseThemes' => [
          'activeTheme' => ['baseTheme' => 'Base theme'],
          'baseTheme' => [],
        ],
        'expected' => [
          'activeTheme' => [
            'components' => [
              $this->themesDir . '/activeTheme/path1',
              $this->themesDir . '/activeTheme/path2',
              $this->themesDir . '/baseTheme/path1',
              $this->themesDir . '/baseTheme/path2',
              $this->modulesDir . '/weight2/path1',
              $this->modulesDir . '/weight2/path2',
              $this->modulesDir . '/weight1/path1',
              $this->modulesDir . '/weight1/path2',
              $this->modulesDir . '/components/path1',
              $this->modulesDir . '/components/path2',
            ],
            'baseTheme' => [
              $this->themesDir . '/baseTheme/path3',
              $this->themesDir . '/baseTheme/path4',
              $this->modulesDir . '/weight2/path3',
              $this->modulesDir . '/weight2/path4',
              $this->modulesDir . '/weight1/path3',
              $this->modulesDir . '/weight1/path4',
            ],
          ],
          'baseTheme' => [
            'components' => [
              $this->themesDir . '/baseTheme/path1',
              $this->themesDir . '/baseTheme/path2',
              $this->modulesDir . '/weight2/path1',
              $this->modulesDir . '/weight2/path2',
              $this->modulesDir . '/weight1/path1',
              $this->modulesDir . '/weight1/path2',
              $this->modulesDir . '/components/path1',
              $this->modulesDir . '/components/path2',
            ],
            'baseTheme' => [
              $this->themesDir . '/baseTheme/path3',
              $this->themesDir . '/baseTheme/path4',
              $this->modulesDir . '/weight2/path3',
              $this->modulesDir . '/weight2/path4',
              $this->modulesDir . '/weight1/path3',
              $this->modulesDir . '/weight1/path4',
            ],
          ],
        ],
      ],
      'removes protected namespaces with no components data in info.yml' => [
        'moduleInfo' => [
          'system' => [
            'name' => 'System',
            'type' => 'module',
            'package' => 'Core',
          ],
          'components' => [
            'name' => 'Components!',
            'type' => 'module',
            'components' => [
              'namespaces' => [
                'system' => 'system',
                'classy' => 'classy',
              ],
            ],
          ],
        ],
        'themeInfo' => [
          'classy' => [
            'name' => 'Classy',
            'type' => 'theme',
          ],
          'zen' => [
            'name' => 'Zen',
            'type' => 'theme',
            'components' => [
              'namespaces' => [
                'zen' => 'zen-namespace',
                // All three namespaces should be removed.
                'system' => 'system',
                'components' => 'components-namespace',
                'classy' => 'classy',
              ],
            ],
          ],
        ],
        'getPath' => [
          ['components', $this->modulesDir . '/components'],
          ['zen', $this->themesDir . '/zen'],
        ],
        'getBaseThemes' => [
          'classy' => [],
          'zen' => [],
        ],
        'expected' => [
          'zen' => [
            'zen' => [
              $this->themesDir . '/zen/zen-namespace',
            ],
          ],
          'classy' => [],
        ],
        'expectedWarnings' => [
          'The Components! module attempted to alter the protected Twig namespace, system, owned by the System module. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Components! module attempted to alter the protected Twig namespace, classy, owned by the Classy theme. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Zen theme attempted to alter the protected Twig namespace, system, owned by the System module. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Zen theme attempted to alter the protected Twig namespace, components, owned by the Components! module. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Zen theme attempted to alter the protected Twig namespace, classy, owned by the Classy theme. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
        ],
      ],
      'namespace is not protected if default namespace is used' => [
        'moduleInfo' => [
          'system' => [
            'name' => 'System',
            'type' => 'module',
            'package' => 'Core',
          ],
          'components' => [
            'name' => 'Components!',
            'type' => 'module',
            'components' => [
              'namespaces' => [
                'system' => 'system',
                'components' => 'default-namespace',
                'classy' => 'classy',
              ],
            ],
          ],
        ],
        'themeInfo' => [
          'classy' => [
            'name' => 'Classy',
            'type' => 'theme',
          ],
          'zen' => [
            'name' => 'Zen',
            'type' => 'theme',
            'components' => [
              'namespaces' => [
                'zen' => 'zen-namespace',
                'system' => 'system',
                'components' => 'components-namespace',
                'classy' => 'classy',
              ],
            ],
          ],
        ],
        'getPath' => [
          ['components', $this->modulesDir . '/components'],
          ['zen', $this->themesDir . '/zen'],
        ],
        'getBaseThemes' => [
          'classy' => [],
          'zen' => [],
        ],
        'expected' => [
          'zen' => [
            'zen' => [
              $this->themesDir . '/zen/zen-namespace',
            ],
            'components' => [
              $this->themesDir . '/zen/components-namespace',
              $this->modulesDir . '/components/default-namespace',
            ],
          ],
          'classy' => [
            'components' => [
              $this->modulesDir . '/components/default-namespace',
            ],
          ],
        ],
        'expectedWarnings' => [
          'The Components! module attempted to alter the protected Twig namespace, system, owned by the System module. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Components! module attempted to alter the protected Twig namespace, classy, owned by the Classy theme. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Zen theme attempted to alter the protected Twig namespace, system, owned by the System module. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Zen theme attempted to alter the protected Twig namespace, classy, owned by the Classy theme. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
        ],
      ],
      'namespace is not protected if manual opt-in .info.yml flag is used' => [
        'moduleInfo' => [
          'system' => [
            'name' => 'System',
            'type' => 'module',
            'package' => 'Core',
          ],
          'components' => [
            'name' => 'Components!',
            'type' => 'module',
            'components' => [
              'allow_default_namespace_reuse' => TRUE,
            ],
          ],
        ],
        'themeInfo' => [
          'classy' => [
            'name' => 'Classy',
            'type' => 'theme',
          ],
          'zen' => [
            'name' => 'Zen',
            'type' => 'theme',
            'components' => [
              'namespaces' => [
                'zen' => 'zen-namespace',
                'system' => 'system',
                'components' => 'components-namespace',
                'classy' => 'classy',
              ],
            ],
          ],
        ],
        'getPath' => [
          ['components', $this->modulesDir . '/components'],
          ['zen', $this->themesDir . '/zen'],
        ],
        'getBaseThemes' => [
          'classy' => [],
          'zen' => [],
        ],
        'expected' => [
          'zen' => [
            'zen' => [
              $this->themesDir . '/zen/zen-namespace',
            ],
            'components' => [
              $this->themesDir . '/zen/components-namespace',
            ],
          ],
          'classy' => [],
        ],
        'expectedWarnings' => [
          'The Zen theme attempted to alter the protected Twig namespace, system, owned by the System module. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
          'The Zen theme attempted to alter the protected Twig namespace, classy, owned by the Classy theme. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.',
        ],
      ],
    ];
  }

  /**
   * Tests normalizing components data from extension .info.yml files.
   *
   * @param array $getAllInstalledInfo
   *   The array returned by extensionList::getAllInstalledInfo().
   * @param array $getPath
   *   The PHPUnit returnValueMap array for extensionList::getPath().
   * @param null|array $getBaseThemes
   *   A theme-name-keyed array of return values for
   *   themeExtensionList::getBaseThemes().
   * @param array $expected
   *   The expected result.
   *
   * @throws \ReflectionException
   *
   * @covers ::normalizeExtensionListInfo
   *
   * @dataProvider providerTestNormalizeExtensionListInfo
   */
  public function testNormalizeExtensionListInfo(array $getAllInstalledInfo, array $getPath, ?array $getBaseThemes, array $expected) {
    $this->systemUnderTest = $this->newSystemUnderTest();

    // Mock the method param with the test data.
    $extensionList = $this->createMock(
      is_null($getBaseThemes)
        ? '\Drupal\Core\Extension\ModuleExtensionList'
        : '\Drupal\Core\Extension\ThemeExtensionList'
    );
    $extensionList
      ->method('getAllInstalledInfo')
      ->willReturn($getAllInstalledInfo);
    if (!empty($getPath)) {
      $extensionList
        ->method('getPath')
        ->will($this->returnValueMap($getPath));
    }
    if (!is_null($getBaseThemes)) {
      $themeList = [];
      foreach ($getAllInstalledInfo as $info) {
        $extension = $this->createMock('\Drupal\Core\Extension\Extension');
        $extension->method('getName')->willReturn($info['name']);
        $extension->method('getType')->willReturn('theme');
        $themeList[] = $extension;
      }
      $valueMap = [];
      foreach ($getAllInstalledInfo as $themeName => $info) {
        $valueMap[] = [$themeList, $themeName, $getBaseThemes[$themeName]];
      }
      $extensionList
        ->method('getList')
        ->willReturn($themeList);
      $extensionList
        ->method('getBaseThemes')
        ->will($this->returnValueMap($valueMap));
    }

    // Use reflection to test a protected method.
    $result = $this->invokeProtectedMethod($this->systemUnderTest, 'normalizeExtensionListInfo', $extensionList);
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Provides test data to ::testNormalizeNamespacePaths().
   *
   * @see testNormalizeNamespacePaths()
   */
  public function providerTestNormalizeExtensionListInfo(): array {
    return [
      'saves extension info, including package' => [
        'getAllInstalledInfo' => [
          'system' => [
            'name' => 'System',
            'type' => 'module',
            'package' => 'Core',
            'no-components' => 'system-value',
          ],
        ],
        'getPath' => [],
        'getBaseThemes' => NULL,
        'expected' => [
          'system' => [
            'extensionInfo' => [
              'name' => 'System',
              'type' => 'module',
              'package' => 'Core',
            ],
            'namespaces' => [],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
      ],
      'saves extension info, even if no package' => [
        'getAllInstalledInfo' => [
          'system' => [
            'name' => 'System',
            'type' => 'module',
          ],
        ],
        'getPath' => [],
        'getBaseThemes' => NULL,
        'expected' => [
          'system' => [
            'extensionInfo' => [
              'name' => 'System',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
      ],
      'Ignore namespaces using deprecated 1.x API' => [
        'getAllInstalledInfo' => [
          'harriet_tubman' => [
            'name' => 'Harriet Tubman',
            'type' => 'module',
            'component-libraries' => [
              'harriet_tubman' => [
                'paths' => ['deprecated'],
              ],
            ],
          ],
        ],
        'getPath' => [],
        'getBaseThemes' => NULL,
        'expected' => [
          'harriet_tubman' => [
            'extensionInfo' => [
              'name' => 'Harriet Tubman',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
      ],
      'namespaces data is normalized' => [
        'getAllInstalledInfo' => [
          'phillis_wheatley' => [
            'name' => 'Phillis Wheatley',
            'type' => 'module',
            'components' => [
              'namespaces' => [
                // Namespaces path is array.
                'wheatley' => ['components'],
                // Namespaces path is string.
                'wheatley_too' => 'templates',
                // Namespace path is relative to Drupal root.
                'wheatley_adjacent' => [
                  '/libraries/chapman/components',
                  '/../vendor/vendorOrg/vendorComponents',
                ],
              ],
            ],
          ],
        ],
        'getPath' => [
          ['phillis_wheatley', $this->modulesDir . '/phillis_wheatley'],
        ],
        'getBaseThemes' => NULL,
        'expected' => [
          'phillis_wheatley' => [
            'extensionInfo' => [
              'name' => 'Phillis Wheatley',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [
              'wheatley' => [$this->modulesDir . '/phillis_wheatley/components'],
              'wheatley_too' => [$this->modulesDir . '/phillis_wheatley/templates'],
              'wheatley_adjacent' => [
                'libraries/chapman/components',
                '../vendor/vendorOrg/vendorComponents',
              ],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
      ],
      'Manual opt-in of default namespace reuse' => [
        'getAllInstalledInfo' => [
          'components' => [
            'name' => 'Components!',
            'type' => 'module',
            'components' => [
              'allow_default_namespace_reuse' => TRUE,
            ],
          ],
        ],
        'getPath' => [],
        'getBaseThemes' => NULL,
        'expected' => [
          'components' => [
            'extensionInfo' => [
              'name' => 'Components!',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [],
            'allow_default_namespace_reuse' => TRUE,
          ],
        ],
      ],
      'Theme extensionList adds baseThemes' => [
        'getAllInstalledInfo' => [
          'activeTheme' => [
            'name' => 'Active Theme!',
            'type' => 'theme',
            'base theme' => 'baseTheme',
            'components' => [
              'namespaces' => [
                'activeTheme' => 'active',
                'components' => 'components',
              ],
            ],
          ],
          'baseTheme' => [
            'name' => 'Base Theme',
            'type' => 'theme',
            'base theme' => 'basestTheme',
            'components' => [
              'namespaces' => [
                'components' => 'components',
              ],
            ],
          ],
          'basestTheme' => [
            'name' => 'Basest Theme',
            'type' => 'theme',
            'components' => [
              'namespaces' => [
                'components' => 'components',
              ],
            ],
          ],
        ],
        'getPath' => [
          ['activeTheme', $this->themesDir . '/activeTheme'],
          ['baseTheme', $this->themesDir . '/baseTheme'],
          ['basestTheme', $this->themesDir . '/basestTheme'],
        ],
        'getBaseThemes' => [
          'activeTheme' => [
            'basestTheme' => 'Basest Theme',
            'baseTheme' => 'Base Theme',
          ],
          'baseTheme' => [
            'basestTheme' => 'Basest Theme',
          ],
          'basestTheme' => [],
        ],
        'expected' => [
          'activeTheme' => [
            'extensionInfo' => [
              'name' => 'Active Theme!',
              'type' => 'theme',
              'package' => '',
              'baseThemes' => ['basestTheme', 'baseTheme'],
            ],
            'namespaces' => [
              'activeTheme' => [$this->themesDir . '/activeTheme/active'],
              'components' => [$this->themesDir . '/activeTheme/components'],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
          'baseTheme' => [
            'extensionInfo' => [
              'name' => 'Base Theme',
              'type' => 'theme',
              'package' => '',
              'baseThemes' => ['basestTheme'],
            ],
            'namespaces' => [
              'components' => [$this->themesDir . '/baseTheme/components'],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
          'basestTheme' => [
            'extensionInfo' => [
              'name' => 'Basest Theme',
              'type' => 'theme',
              'package' => '',
              'baseThemes' => [],
            ],
            'namespaces' => [
              'components' => [$this->themesDir . '/basestTheme/components'],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
      ],
      'Handles invalid base themes' => [
        'getAllInstalledInfo' => [
          'activeTheme' => [
            'name' => 'Active Theme!',
            'type' => 'theme',
            'base theme' => 'baseTheme',
            'components' => [
              'namespaces' => [
                'activeTheme' => 'active',
                'components' => 'components',
              ],
            ],
          ],
          'baseTheme' => [
            'name' => 'Base Theme',
            'type' => 'theme',
            'base theme' => 'basestTheme',
            'components' => [
              'namespaces' => [
                'components' => 'components',
              ],
            ],
          ],
        ],
        'getPath' => [
          ['activeTheme', $this->themesDir . '/activeTheme'],
          ['baseTheme', $this->themesDir . '/baseTheme'],
        ],
        'getBaseThemes' => [
          'activeTheme' => [
            'basestTheme' => NULL,
            'baseTheme' => 'Base Theme',
          ],
          'baseTheme' => [
            'basestTheme' => NULL,
          ],
        ],
        'expected' => [
          'activeTheme' => [
            'extensionInfo' => [
              'name' => 'Active Theme!',
              'type' => 'theme',
              'package' => '',
              'baseThemes' => ['baseTheme'],
            ],
            'namespaces' => [
              'activeTheme' => [$this->themesDir . '/activeTheme/active'],
              'components' => [$this->themesDir . '/activeTheme/components'],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
          'baseTheme' => [
            'extensionInfo' => [
              'name' => 'Base Theme',
              'type' => 'theme',
              'package' => '',
              'baseThemes' => [],
            ],
            'namespaces' => [
              'components' => [$this->themesDir . '/baseTheme/components'],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
      ],
    ];
  }

  /**
   * Tests registering protected namespaces.
   *
   * @param array $extensionInfo
   *   The array of extensions.
   * @param array $expected
   *   The expected value of protectedNamespaces.
   *
   * @throws \ReflectionException
   *
   * @covers ::findProtectedNamespaces
   *
   * @dataProvider providerTestFindProtectedNamespaces
   */
  public function testFindProtectedNamespaces(array $extensionInfo, array $expected): void {
    // Test that hook_protected_twig_namespaces_alter() is called for modules.
    $moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandler
      ->method('alter')
      ->withConsecutive(
        ['protected_twig_namespaces', $expected, NULL, NULL],
      );

    // Test that hook_protected_twig_namespaces_alter() is called for themes.
    $themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $themeManager
      ->method('alter')
      ->withConsecutive(
        ['protected_twig_namespaces', $expected, NULL, NULL],
      );

    // Mock the system under test.
    $this->systemUnderTest = $this->newSystemUnderTest(
      NULL,
      NULL,
      $moduleHandler,
      $themeManager
    );

    $result = $this->invokeProtectedMethod($this->systemUnderTest, 'findProtectedNamespaces', $extensionInfo);
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Provides test data to ::testFindProtectedNamespaces().
   *
   * @see testFindProtectedNamespaces()
   */
  public function providerTestFindProtectedNamespaces(): array {
    return [
      'Manual opt-in' => [
        'extensionInfo' => [
          'edna_lewis' => [
            'extensionInfo' => [
              'name' => 'Edna Lewis',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [],
            'allow_default_namespace_reuse' => TRUE,
          ],
        ],
        'expected' => [],
      ],
      'Default namespace is defined' => [
        'extensionInfo' => [
          'edna_lewis' => [
            'extensionInfo' => [
              'name' => 'Edna Lewis',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [
              'edna_lewis' => [
                $this->modulesDir . '/edna_lewis/components',
              ],
            ],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
        'expected' => [],
      ],
      'Namespace is protected' => [
        'extensionInfo' => [
          'edna_lewis' => [
            'extensionInfo' => [
              'name' => 'Edna Lewis',
              'type' => 'module',
              'package' => '',
            ],
            'namespaces' => [],
            'allow_default_namespace_reuse' => FALSE,
          ],
        ],
        'expected' => [
          'edna_lewis' => [
            'name' => 'Edna Lewis',
            'type' => 'module',
            'package' => '',
          ],
        ],
      ],
    ];
  }

}
