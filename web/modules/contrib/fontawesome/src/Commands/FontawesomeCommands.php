<?php

namespace Drupal\fontawesome\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Archiver\ArchiverManager;

/**
 * A Drush commandfile for Font Awesome module.
 */
class FontawesomeCommands extends DrushCommands {

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * File system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Archive manager service.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, FileSystemInterface $file_system, ArchiverManager $archiver_manager) {
    parent::__construct();

    $this->libraryDiscovery = $library_discovery;
    $this->fileSystem = $file_system;
    $this->archiverManager = $archiver_manager;
  }

  /**
   * Downloads the required Fontawesome library.
   *
   * @param string $path
   *   Optional path to module. If omitted Drush will use the default location.
   *
   * @command fa:download
   * @aliases fadl,fa-download
   */
  public function download($path = '') {

    if (empty($path)) {
      // We have dependencies on libraries module so no need to check for that
      // TODO: any way to get path for libraries directory?
      // Just in case if it is site specific? e.g. sites/domain.com/libraries ?
      $path = DRUPAL_ROOT . '/libraries/fontawesome';
    }

    // Create the path if it does not exist yet. Added substr check for
    // preventing any wrong attempts or hacks !
    if (substr($path, -11) == 'fontawesome' && !is_dir($path)) {
      $this->fileSystem->mkdir($path);
    }
    if (is_dir($path . '/css')) {
      $this->logger()->notice(dt('Font Awesome already present at @path. No download required.', ['@path' => $path]));
      return;
    }

    // Load the Font Awesome defined library.
    if ($fontawesome_library = $this->libraryDiscovery->getLibraryByName('fontawesome', 'fontawesome.svg')) {

      // Download the file.
      $destination = tempnam(sys_get_temp_dir(), 'file.') . "tar.gz";
      system_retrieve_file($fontawesome_library['remote'], $destination);
      if (!file_exists($destination)) {
        // Remove the directory.
        $this->fileSystem->rmdir($path);
        $this->logger()->error(dt('Drush was unable to download the Font Awesome library from @remote.', [
          '@remote' => $fontawesome_library['remote'],
        ]));
        return;
      }
      $this->fileSystem->move($destination, $path . '/fontawesome.zip');
      if (!file_exists($path . '/fontawesome.zip')) {
        // Remove the directory where we tried to install.
        $this->fileSystem->rmdir($path);
        $this->logger()->error(dt('Error: unable to download Fontawesome library from @remote', [
          '@remote' => $fontawesome_library['remote'],
        ]));
        return;
      }

      // Unzip the file.
      /** @var \Drupal\Core\Archiver\ArchiverInterface $zipFile */
      $zipFile = $this->archiverManager->getInstance(['filepath' => $path . '/fontawesome.zip']);
      $zipFile->extract($path);

      // Remove the downloaded zip file.
      $this->fileSystem->unlink($path . '/fontawesome.zip');

      // Move the file.
      $this->fileSystem->move($path . '/fontawesome-free-' . $fontawesome_library['version'] . '-web', $this->fileSystem->getTempDirectory() . '/temp_fontawesome', FileSystemInterface::EXISTS_REPLACE);
      $this->fileSystem->rmdir($path);
      $this->fileSystem->move($this->fileSystem->getTempDirectory() . '/temp_fontawesome', $path, FileSystemInterface::EXISTS_REPLACE);

      // Success.
      $this->logger()->notice(dt('Fontawesome library has been successfully downloaded to @path.', [
        '@path' => $path,
      ]));
    }
    else {
      $this->logger()->error(dt('Drush was unable to load the Font Awesome library'));
    }
  }

}
