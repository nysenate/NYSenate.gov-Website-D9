<?php

namespace Drupal\yaml_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content field import event for event listeners.
 */
class FieldImportEvent extends DataImportEvent {

  /**
   * The entity being populated with field data.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The field object being populated.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $field;

  /**
   * Constructs a yaml content field import event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being populated with field data.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object being populated.
   * @param array $content_data
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function __construct(ContentLoaderInterface $loader, EntityInterface $entity, FieldItemListInterface $field, array $content_data) {
    parent::__construct($loader, $content_data);

    $this->entity = $entity;
    $this->field = $field;
  }

  /**
   * Gets the entity being populated with field data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being populated with field data.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the field object being populated.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field object being populated.
   */
  public function getField() {
    return $this->field;
  }

}
