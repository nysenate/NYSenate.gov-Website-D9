<?php

namespace Drupal\entity_usage\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Implementation of Entity Usage events.
 */
class EntityUsageEvent extends Event {

  /**
   * The target entity ID.
   *
   * @var string
   */
  protected $targetEntityId;

  /**
   * The target entity type.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * The source entity ID.
   *
   * @var string
   */
  protected $sourceEntityId;

  /**
   * The source entity type.
   *
   * @var string
   */
  protected $sourceEntityType;

  /**
   * The source entity language code.
   *
   * @var string
   */
  protected $sourceEntityLangcode;

  /**
   * The source entity revision ID.
   *
   * @var string
   */
  protected $sourceEntityRevisionId;

  /**
   * The method used to relate source entity with the target entity.
   *
   * @var string
   */
  protected $method;

  /**
   * The name of the field in the source entity using the target entity.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The number of references to add or remove.
   *
   * @var string
   */
  protected $count;

  /**
   * EntityUsageEvents constructor.
   *
   * @param int $target_id
   *   The target entity ID.
   * @param string $target_type
   *   The target entity type.
   * @param int $source_id
   *   The source entity ID.
   * @param string $source_type
   *   The source entity type.
   * @param string $source_langcode
   *   The source entity language code.
   * @param string $source_vid
   *   The source entity revision ID.
   * @param string $method
   *   The method or way the two entities are being referenced.
   * @param string $field_name
   *   The name of the field in the source entity using the target entity.
   * @param int $count
   *   The number of references to add or remove.
   */
  public function __construct($target_id = NULL, $target_type = NULL, $source_id = NULL, $source_type = NULL, $source_langcode = NULL, $source_vid = NULL, $method = NULL, $field_name = NULL, $count = NULL) {
    $this->targetEntityId = $target_id;
    $this->targetEntityType = $target_type;
    $this->sourceEntityId = $source_id;
    $this->sourceEntityType = $source_type;
    $this->sourceEntityLangcode = $source_langcode;
    $this->sourceEntityRevisionId = $source_vid;
    $this->method = $method;
    $this->fieldName = $field_name;
    $this->count = $count;
  }

  /**
   * Sets the target entity id.
   *
   * @param int $id
   *   The target entity id.
   */
  public function setTargetEntityId($id) {
    $this->targetEntityId = $id;
  }

  /**
   * Sets the target entity type.
   *
   * @param string $type
   *   The target entity type.
   */
  public function setTargetEntityType($type) {
    $this->targetEntityType = $type;
  }

  /**
   * Sets the source entity id.
   *
   * @param int $id
   *   The source entity id.
   */
  public function setSourceEntityId($id) {
    $this->sourceEntityId = $id;
  }

  /**
   * Sets the source entity type.
   *
   * @param string $type
   *   The source entity type.
   */
  public function setSourceEntityType($type) {
    $this->sourceEntityType = $type;
  }

  /**
   * Sets the source entity language code.
   *
   * @param string $langcode
   *   The source entity language code.
   */
  public function setSourceEntityLangcode($langcode) {
    $this->sourceEntityLangcode = $langcode;
  }

  /**
   * Sets the source entity revision ID.
   *
   * @param string $vid
   *   The source entity revision ID.
   */
  public function setSourceEntityRevisionId($vid) {
    $this->sourceEntityRevisionId = $vid;
  }

  /**
   * Sets the method used to relate source entity with the target entity.
   *
   * @param string $method
   *   The source method.
   */
  public function setMethod($method) {
    $this->method = $method;
  }

  /**
   * Sets the field name.
   *
   * @param string $field_name
   *   The field name.
   */
  public function setFieldName($field_name) {
    $this->fieldName = $field_name;
  }

  /**
   * Sets the count.
   *
   * @param int $count
   *   The number od references to add or remove.
   */
  public function setCount($count) {
    $this->count = $count;
  }

  /**
   * Gets the target entity id.
   *
   * @return null|string
   *   The target entity id or NULL.
   */
  public function getTargetEntityId() {
    return $this->targetEntityId;
  }

  /**
   * Gets the target entity type.
   *
   * @return null|string
   *   The target entity type or NULL.
   */
  public function getTargetEntityType() {
    return $this->targetEntityType;
  }

  /**
   * Gets the source entity id.
   *
   * @return null|int
   *   The source entity id or NULL.
   */
  public function getSourceEntityId() {
    return $this->sourceEntityId;
  }

  /**
   * Gets the source entity type.
   *
   * @return null|string
   *   The source entity type or NULL.
   */
  public function getSourceEntityType() {
    return $this->sourceEntityType;
  }

  /**
   * Gets the source entity language code.
   *
   * @return null|string
   *   The source entity language code or NULL.
   */
  public function getSourceEntityLangcode() {
    return $this->sourceEntityLangcode;
  }

  /**
   * Gets the source entity revision ID.
   *
   * @return null|string
   *   The source entity revision ID or NULL.
   */
  public function getSourceEntityRevisionId() {
    return $this->sourceEntityRevisionId;
  }

  /**
   * Gets the method used to relate source entity with the target entity.
   *
   * @return null|string
   *   The method or NULL.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Gets the field name.
   *
   * @return null|string
   *   The field name or NULL.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Gets the count.
   *
   * @return null|int
   *   The number of references to add or remove or NULL.
   */
  public function getCount() {
    return $this->count;
  }

}
