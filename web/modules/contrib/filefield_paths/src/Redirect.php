<?php

namespace Drupal\filefield_paths;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Language\Language;
use Psr\Log\LoggerInterface;

/**
 * Service for creating file redirects.
 */
class Redirect implements RedirectInterface {

  /**
   * The redirect storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $redirectStorage;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new redirect service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StreamWrapperManagerInterface $stream_wrapper_manager, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->redirectStorage = $entity_type_manager->getStorage('redirect');
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function createRedirect($source, $path, Language $language) {
    $this->logger->debug('Creating redirect from @source to @path.', [
      '@source' => $source,
      '@path'   => $path,
    ]);

    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->redirectStorage->create([]);

    $parsed_source = $this->getPath($source);
    $parsed_path = $this->getPath($path);

    $redirect->setSource($parsed_source);
    $redirect->setRedirect($parsed_path);
    $redirect->setStatusCode($this->configFactory->get('redirect.settings')->get('default_status_code'));

    // Check if the redirect doesn't already exist before saving.
    $hash = $redirect->generateHash($parsed_path, [], $language->getId());
    $redirects = $this->redirectStorage->loadByProperties(['hash' => $hash]);
    if (empty($redirects)) {
      // Redirect does not exist yet, save as new one.
      $redirect->save();
    }
  }

  /**
   * Returns the path to the file, starting from the Drupal root.
   *
   * @param string $file_uri
   *   The file url to get the path for.
   *
   * @return string|null
   *   The file path, if found. Null otherwise.
   */
  protected function getPath($file_uri) {
    if ($wrapper = $this->streamWrapperManager->getViaUri($file_uri)) {
      $directory = $wrapper->getDirectoryPath();
      $target = StreamWrapperManager::getTarget($file_uri);
      return $directory . '/' . $target;
    }
  }

}
