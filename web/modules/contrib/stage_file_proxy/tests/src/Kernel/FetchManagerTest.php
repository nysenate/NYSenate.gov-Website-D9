<?php

namespace Drupal\Tests\stage_file_proxy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stage_file_proxy\DownloadManager;
use Drupal\stage_file_proxy\FetchManager;
use GuzzleHttp\Client;

/**
 * Test stage file proxy module.
 *
 * @coversDefaultClass \Drupal\stage_file_proxy\FetchManager
 *
 * @group stage_file_proxy
 */
class FetchManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'file'];

  /**
   * FetchManager object.
   *
   * @var \Drupal\stage_file_proxy\FetchManager
   */
  protected $fetchManager;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The file logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Filesystem interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The download manager.
   *
   * @var \Drupal\stage_file_proxy\DownloadManagerInterface
   */
  protected DownloadManager $downloadManager;

  /**
   * Before a test method is run, setUp() is invoked.
   *
   * Create new fetchManager object.
   */
  public function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->container->get('file_system');
    $this->config('system.file')->set('default_scheme', 'public')->save();
    $this->client = new Client();
    $this->logger = \Drupal::logger('test_logger');
    $this->configFactory = $this->container->get('config.factory');
    $this->downloadManager = new DownloadManager($this->client, $this->fileSystem, $this->logger, $this->configFactory, \Drupal::lock());

    $this->fetchManager = new FetchManager($this->client, $this->fileSystem, $this->logger, $this->configFactory, $this->downloadManager);
  }

  /**
   * @covers Drupal\stage_file_proxy\FetchManager::styleOriginalPath
   */
  public function testStyleOriginalPath() {
    // Test image style path assuming public file scheme.
    $this->assertEquals('public://example.jpg', $this->fetchManager->styleOriginalPath('styles/icon_50x50_/public/example.jpg'));
  }

  /**
   * Clean up.
   *
   * Once test method has finished running, whether it succeeded or failed,
   * tearDown() will be invoked. Unset the $fetchManager object.
   */
  public function tearDown(): void {
    unset($this->fetchManager);
  }

}
