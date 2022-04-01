<?php

namespace Drupal\yaml_content\Event;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content field import event for event listeners.
 */
class EntityImportEvent extends DataImportEvent {

  /**
   * The entity type object for the entity being created.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Constructs a yaml content entity import event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The field definition for the field being populated.
   * @param array $content_data
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function __construct(ContentLoaderInterface $loader, EntityTypeInterface $entity_type, array $content_data) {
    parent::__construct($loader, $content_data);

    $this->entityType = $entity_type;
  }

  /**
   * Gets the field definition object for the field being populated.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type object for the entity being created.
   */
  public function getEntityType() {
    return $this->entityType;
  }

}
