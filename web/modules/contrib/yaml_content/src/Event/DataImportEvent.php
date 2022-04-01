<?php

namespace Drupal\yaml_content\Event;

use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content data import event for event listeners.
 */
class DataImportEvent extends EventBase {

  /**
   * The parsed content from the file used to create the entity.
   *
   * @var array
   */
  protected $contentData;

  /**
   * Constructs a yaml content entity pre-save event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param array $content_data
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function __construct(ContentLoaderInterface $loader, array $content_data) {
    parent::__construct($loader);

    $this->contentData = $content_data;
  }

  /**
   * Gets the parsed content to populate into the field.
   *
   * @return array
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function getContentData() {
    return $this->contentData;
  }

}
