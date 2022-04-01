<?php

namespace Drupal\yaml_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content entity pre-save event for event listeners.
 */
class EntityPreSaveEvent extends EventBase {

  /**
   * The entity prepared to be saved.
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
   * Constructs a yaml content entity pre-save event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity loaded with content and prepared for saving.
   * @param array $content_data
   *   The parsed content loaded from the content file resulting in this entity.
   */
  public function __construct(ContentLoaderInterface $loader, EntityInterface $entity, array $content_data) {
    parent::__construct($loader);

    $this->entity = $entity;
    $this->contentData = $content_data;
  }

  /**
   * Gets the entity that is populated and prepared for saving.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity prepared to be saved.
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
