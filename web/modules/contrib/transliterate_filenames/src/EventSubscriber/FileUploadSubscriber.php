<?php

namespace Drupal\transliterate_filenames\EventSubscriber;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\transliterate_filenames\SanitizeName;

/**
 * Class FileUploadSubscriber.
 */
class FileUploadSubscriber implements EventSubscriberInterface {

  /**
   * A filename sanitizer service instance.
   *
   * @var \Drupal\transliterate_filenames\SanitizeName
   */
  protected $sanitizeName;

  /**
   * @param \Drupal\transliterate_filenames\SanitizeName $transliterate_filenames_sanitize_name
   */
  public function __construct(SanitizeName $transliterate_filenames_sanitize_name) {
    $this->sanitizeName = $transliterate_filenames_sanitize_name;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FileUploadSanitizeNameEvent::class][] = ['transliterateName', -100];
    return $events;
  }

  /**
   * Transliterates the upload's filename.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   File upload sanitize name event.
   */
  public function transliterateName(FileUploadSanitizeNameEvent $event): void {
    $filename = $event->getFilename();
    $filename = $this->sanitizeName->sanitizeFilename($filename);
    $event->setFilename($filename);
  }

}
