<?php

namespace Drupal\yaml_content\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Base implementation of yaml_content events.
 */
class EventBase extends Event {

  /**
   * The ContentLoader being actively executed and triggering the event.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   */
  protected $contentLoader;

  /**
   * Constructs a yaml content content parsed event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The ContentLoader object that triggered the event.
   */
  public function __construct(ContentLoaderInterface $loader) {
    $this->contentLoader = $loader;
  }

  /**
   * Gets the ContentLoader object that triggered the event.
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
   *   The ContentLoader object that triggered the event.
   */
  public function getContentLoader() {
    return $this->contentLoader;
  }

}
