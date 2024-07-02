<?php

namespace Drupal\Tests\devel_generate\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drupal\devel_generate_example\Plugin\DevelGenerate\ExampleDevelGenerate;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\devel_generate\DevelGeneratePluginManager
 * @group devel_generate
 */
class DevelGenerateManagerTest extends UnitTestCase {

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $discovery;

  /**
   * A list of devel generate plugin definitions.
   *
   * @var array
   */
  protected $definitions = [
    'devel_generate_example' => [
      'id' => 'devel_generate_example',
      'class' => ExampleDevelGenerate::class,
      'url' => 'devel_generate_example',
      'dependencies' => [],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock a Discovery object to replace AnnotationClassDiscovery.
    $this->discovery = $this->createMock(DiscoveryInterface::class);
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($this->definitions));
  }

  /**
   * Test creating an instance of the DevelGenerateManager.
   */
  public function testCreateInstance(): void {
    $namespaces = new \ArrayObject(['Drupal\devel_generate_example' => realpath(__DIR__ . '/../../../modules/devel_generate_example/lib')]);
    $cache_backend = $this->createMock(CacheBackendInterface::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $messenger = $this->createMock(MessengerInterface::class);
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $string_translation = $this->createMock(TranslationInterface::class);

    $manager = new TestDevelGeneratePluginManager(
      $namespaces,
      $cache_backend,
      $module_handler,
      $entity_type_manager,
      $messenger,
      $language_manager,
      $string_translation,
    );
    $manager->setDiscovery($this->discovery);

    $container = new ContainerBuilder();
    $time = $this->createMock(TimeInterface::class);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('messenger', $messenger);
    $container->set('language_manager', $language_manager);
    $container->set('module_handler', $module_handler);
    $container->set('string_translation', $string_translation);
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    $example_instance = $manager->createInstance('devel_generate_example');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf(ExampleDevelGenerate::class, $example_instance);
    $this->assertArrayHasKey('url', $plugin_def);
    $this->assertTrue($plugin_def['url'] == 'devel_generate_example');
  }

}

/**
 * A testing version of DevelGeneratePluginManager.
 */
class TestDevelGeneratePluginManager extends DevelGeneratePluginManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery): void {
    $this->discovery = $discovery;
  }

}
