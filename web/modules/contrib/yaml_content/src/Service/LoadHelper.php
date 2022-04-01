<?php

namespace Drupal\yaml_content\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;
use Psr\Log\LoggerInterface;

/**
 * A helper class to support the content loading process.
 */
class LoadHelper {

  use StringTranslationTrait;

  /**
   * The content loader to use for importing content.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   */
  protected $loader;

  /**
   * The logging channel for recording import events.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the load helper service.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $content_loader
   *   The content loader service to use for content imports.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logging channel for recording import events.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   String translation service for message logging.
   */
  public function __construct(ContentLoaderInterface $content_loader, LoggerInterface $logger, TranslationInterface $translation) {
    $this->loader = $content_loader;
    $this->logger = $logger;

    $this->setStringTranslation($translation);
  }

  /**
   * Import specified yaml content file(s).
   *
   * @param string $directory
   *   The directory path containing the yaml content file(s) to be imported.
   * @param string $file
   *   (Optional) The name of a file (without the trailing `.content.yml` to be
   *   imported or an array of files to import. If this argument is not
   *   provided then all files in the directory matching `*.content.yml`
   *   are queued for import.
   */
  public function importDirectory($directory, $file = NULL) {
    $this->loader->setContentPath($directory);

    // Identify files for import.
    $mask = '/' . (isset($file) ? $file : '.*') . '\.content\.yml/';
    $files = $this->discoverFiles($directory . '/content', $mask);

    $this->importFiles($files);
  }

  /**
   * Import specified yaml content file(s) from a designated module.
   *
   * @param string $module
   *   The module to look for content files within.
   *
   *   This command assumes files will be contained within a `content/` directory
   *   at the top of the module's main directory. Any files within matching the
   *   pattern `*.content.yml` will then be imported.
   * @param string|string[] $file
   *   (Optional) The name of a file to be imported or an array of files to
   *   import. If this argument is not provided then all files in the directory
   *   matching `*.content.yml` are queued for import.
   */
  public function importModule($module, $file = NULL) {
    $path = drupal_get_path('module', $module);

    $this->loader->setContentPath($path);

    // Identify files for import.
    $mask = '/' . (isset($file) ? $file : '.*') . '\.content\.yml/';
    $files = $this->discoverFiles($path . '/content', $mask);

    $this->importFiles($files);
  }

  /**
   * Import specified yaml content file(s) from a designated profile.
   *
   * @param string $profile
   *   The profile to look for content files within.
   *
   *   This command assumes files will be contained within a `content/` directory
   *   at the top of the module's main directory. Any files within matching the
   *   pattern `*.content.yml` will then be imported.
   * @param string|string[] $file
   *   (Optional) The name of a file to be imported or an array of files to
   *   import. If this argument is not provided then all files in the directory
   *   matching `*.content.yml` are queued for import.
   */
  public function importProfile($profile, $file = NULL) {
    $path = drupal_get_path('profile', $profile);

    $this->loader->setContentPath($path);

    // Identify files for import.
    $mask = '/' . (isset($file) ? $file : '.*') . '\.content\.yml/';
    $files = $this->discoverFiles($path . '/content', $mask);

    $this->importFiles($files);
  }

  /**
   * Scan and discover content files for import.
   *
   * The scanner assumes all content files will follow the naming convention of
   * '*.content.yml'.
   *
   * @param string $path
   *   The directory path to be scanned for content files.
   * @param string $mask
   *   (Optional) A file name mask to limit matches in scanned files.
   *
   * @return array
   *   An associative array of objects keyed by filename with the following
   *   properties as returned by FileSystemInterface::scanDirectory():
   *
   *   - 'uri'
   *   - 'filename'
   *   - 'name'
   *
   * @see \Drupal\Core\File\FileSystemInterface::scanDirectory()
   */
  public function discoverFiles($path, $mask = '/.*\.content\.yml/') {
    // Identify files for import.
    $files = \Drupal::service('file_system')->scanDirectory($path, $mask, [
      'key' => 'filename',
      'recurse' => FALSE,
    ]);

    // Sort the files to ensure consistent sequence during imports.
    ksort($files);

    return $files;
  }

  /**
   * Import content files using a Content Loader.
   *
   * @param array $files
   *   An array of file descriptors as loaded by
   *   FileSystemInterface::scanDirectory() keyed by filename. Each of the
   *   listed files will be imported.
   */
  protected function importFiles(array $files) {
    // @todo Verify files before loading for import.
    foreach ($files as $filename => $file) {
      // Log pre-import notices.
      \Drupal::messenger()->addMessage($this->t('Importing content: %file', [
        '%file' => $filename,
      ]));
      $this->logger->notice('Importing content: %file', [
        '%file' => $filename,
      ]);

      $loaded = $this->loader->loadContent($filename);

      // Log post-import summaries.
      \Drupal::messenger()->addMessage($this->t('Imported %count items from %file', [
        '%count' => count($loaded),
        '%file' => $filename,
      ]));
      $this->logger->notice('Imported %count items from %file', [
        '%count' => count($loaded),
        '%file' => $filename,
      ]);
    }
  }

}
