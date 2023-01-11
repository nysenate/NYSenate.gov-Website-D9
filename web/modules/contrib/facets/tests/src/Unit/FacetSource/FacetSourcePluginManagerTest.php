<?php

namespace Drupal\Tests\facets\Unit\FacetSource;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for plugin manager.
 *
 * @group facets
 */
class FacetSourcePluginManagerTest extends UnitTestCase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $discovery;

  /**
   * The plugin factory.
   *
   * @var \Drupal\Component\Plugin\Factory\DefaultFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $factory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The plugin manager under test.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  public $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->discovery = $this->createMock(DiscoveryInterface::class);

    $this->factory = $this->createMock(DefaultFactory::class);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->cache = $this->createMock(CacheBackendInterface::class);

    $namespaces = new \ArrayObject();

    $this->sut = new FacetSourcePluginManager($namespaces, $this->cache, $this->moduleHandler);
    $discovery_property = new \ReflectionProperty($this->sut, 'discovery');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($this->sut, $this->discovery);
    $factory_property = new \ReflectionProperty($this->sut, 'factory');
    $factory_property->setAccessible(TRUE);
    $factory_property->setValue($this->sut, $this->factory);
  }

  /**
   * Tests plugin manager constructor.
   */
  public function testConstruct() {
    $namespaces = new \ArrayObject();
    $sut = new FacetSourcePluginManager($namespaces, $this->cache, $this->moduleHandler);
    $this->assertInstanceOf(FacetSourcePluginManager::class, $sut);
  }

  /**
   * Tests plugin manager's getDefinitions method.
   */
  public function testGetDefinitions() {
    $definitions = [
      'foo' => [
        'id' => 'foo_bar',
        'label' => 'Foo bar',
        'description' => 'test',
        'display_id' => 'foo',
      ],
    ];
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertSame($definitions, $this->sut->getDefinitions());
  }

  /**
   * Tests plugin manager definitions.
   *
   * @dataProvider invalidDefinitions
   */
  public function testInvalidDefinitions($invalid_definition) {
    $definitions = ['foo' => [$invalid_definition]];

    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->expectException(PluginException::class);
    $this->sut->getDefinitions();
  }

  /**
   * Provides invalid definitions.
   *
   * @return array
   *   An invalid data provider.
   */
  public function invalidDefinitions() {
    return [
      'only id' => ['id' => 'owl'],
      'only display_id' => ['display_id' => 'search_api:owl'],
      'only label' => ['label' => 'Owl'],
      'no label' => ['id' => 'owl', 'display_id' => 'Owl'],
    ];
  }

}
