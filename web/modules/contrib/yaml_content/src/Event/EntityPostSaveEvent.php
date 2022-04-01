<?php

namespace Drupal\yaml_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content entity post-save event for event listeners.
 */
class EntityPostSaveEvent extends EventBase {

  /**
   * The imported and saved entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The parsed content from the file used to create the entity.
   *
   * @var array
   */
  protected $contentData;

  /**
   * Constructs a yaml content entity post-save event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity loaded with content and saved.
   * @param array $content_data
   *   The parsed content loaded from the content file resulting in this entity.
   */
  public function __construct(ContentLoaderInterface $loader, EntityInterface $entity, array $content_data) {
    parent::__construct($loader);

    $this->entity = $entity;
    $this->contentData = $content_data;
  }

  /**
   * Gets the entity that was imported and saved.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The saved entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the parsed content loaded from the content file to create the entity.
   *
   * @return array
   *   The parsed content from the file used to create the entity.
   */
  public function getContentData() {
    return $this->contentData;
  }

}
