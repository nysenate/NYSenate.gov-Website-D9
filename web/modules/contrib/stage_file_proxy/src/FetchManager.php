<?php

namespace Drupal\stage_file_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Fetch manager.
 */
class FetchManager implements FetchManagerInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * {@inheritdoc}
   */
  public function __construct(Client $client, FileSystemInterface $file_system, LoggerInterface $logger, ConfigFactoryInterface $config_factory, DownloadManagerInterface $download_manager = NULL) {
    $this->client = $client;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->configFactory = $config_factory;

    if (is_null($download_manager)) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $download_manager argument is deprecated in stage_file_proxy:2.1.0 and will be required in stage_file_proxy:3.0.0. See https://www.drupal.org/project/stage_file_proxy/issues/3375749', E_USER_DEPRECATED);
      // phpcs:ignore
      $this->downloadManager = \Drupal::service('stage_file_proxy.download_manager');
    }
    else {
      $this->downloadManager = $download_manager;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($server, $remote_file_dir, $relative_path, array $options) {
    return $this->downloadManager->fetch($server, $remote_file_dir, $relative_path, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function filePublicPath() {
    return $this->downloadManager->filePublicPath();
  }

  /**
   * {@inheritdoc}
   */
  public function styleOriginalPath($uri, $style_only = TRUE) {
    return $this->downloadManager->styleOriginalPath($uri, $style_only);
  }

  /**
   * Use write & rename instead of write.
   *
   * Perform the replace operation. Since there could be multiple processes
   * writing to the same file, the best option is to create a temporary file in
   * the same directory and then rename it to the destination. A temporary file
   * is needed if the directory is mounted on a separate machine; thus ensuring
   * the rename command stays local.
   *
   * @param string $destination
   *   A string containing the destination location.
   * @param string $data
   *   A string containing the contents of the file.
   *
   * @deprecated in stage_file_proxy:2.1.0 and is removed from
   *   stage_file_proxy:3.0.0. This function is no longer used by Stage File
   *   Proxy itself.
   *
   * @see https://www.drupal.org/project/stage_file_proxy/issues/3282542
   *
   * @return bool
   *   True if write was successful. False if write or rename failed.
   */
  protected function writeFile($destination, $data) {
    // Get a temporary filename in the destination directory.
    $dir = $this->fileSystem->dirname($destination) . '/';
    $temporary_file = $this->fileSystem->tempnam($dir, 'stage_file_proxy_');
    $temporary_file_copy = $temporary_file;

    // Get the extension of the original filename and append it to the temp file
    // name. Preserves the mime type in different stream wrapper
    // implementations.
    $parts = pathinfo($destination);
    $extension = isset($parts['extension']) ? '.' . $parts['extension'] : '';
    if ($extension === '.gz') {
      $parts = pathinfo($parts['filename']);
      $extension = '.' . $parts['extension'] . $extension;
    }
    // Move temp file into the destination dir if not in there.
    // Add the extension on as well.
    $temporary_file = str_replace(substr($temporary_file, 0, strpos($temporary_file, 'stage_file_proxy_')), $dir, $temporary_file) . $extension;

    // Preform the rename, adding the extension to the temp file.
    if (!@rename($temporary_file_copy, $temporary_file)) {
      // Remove if rename failed.
      @unlink($temporary_file_copy);
      return FALSE;
    }

    // Save to temporary filename in the destination directory.
    $filepath = $this->fileSystem->saveData($data, $temporary_file, FileSystemInterface::EXISTS_REPLACE);

    // Perform the rename operation if the write succeeded.
    if ($filepath) {
      if (!@rename($filepath, $destination)) {
        // Unlink and try again for windows. Rename on windows does not replace
        // the file if it already exists.
        @unlink($destination);
        if (!@rename($filepath, $destination)) {
          // Remove temporary_file if rename failed.
          @unlink($filepath);
        }
      }
    }

    // Final check; make sure file exists and is not empty.
    $result = FALSE;
    if (file_exists($destination) && filesize($destination) > 0) {
      $result = TRUE;
    }
    return $result;
  }

}
