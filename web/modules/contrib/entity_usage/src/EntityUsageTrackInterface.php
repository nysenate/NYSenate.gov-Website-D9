<?php

namespace Drupal\entity_usage;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for entity_usage track methods.
 *
 * Track plugins use any arbitrary method to link two entities together.
 * Examples include:
 *
 * - Entities related through an entity_reference field are tracked using the
 *   "entity_reference" method.
 * - Entities embedded into other entities are tracked using the "embed" method.
 */
interface EntityUsageTrackInterface extends PluginInspectionInterface {

  /**
   * Returns the tracking method unique id.
   *
   * @return string
   *   The tracking method id.
   */
  public function getId();

  /**
   * Returns the tracking method label.
   *
   * @return string
   *   The tracking method label.
   */
  public function getLabel();

  /**
   * Returns the tracking method description.
   *
   * @return string
   *   The tracking method description, or an empty string is non defined.
   */
  public function getDescription();

  /**
   * Returns the field types this plugin is capable of tracking.
   *
   * @return array
   *   An indexed array of field type names, as defined in the plugin's
   *   annotation under the key "field_types".
   */
  public function getApplicableFieldTypes();

  /**
   * Track usage updates on the creation of entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   */
  public function trackOnEntityCreation(EntityInterface $source_entity);

  /**
   * Track usage updates on the edition of entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   */
  public function trackOnEntityUpdate(EntityInterface $source_entity);

  /**
   * Retrieve fields of the given types on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity object.
   * @param string[] $field_types
   *   A list of field types.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of fields that could reference to other content entities.
   */
  public function getReferencingFields(EntityInterface $source_entity, array $field_types);

  /**
   * Retrieve the target entity(ies) from a field item value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item to get the target entity(ies) from.
   *
   * @return string[]
   *   An indexed array of strings where each target entity type and ID are
   *   concatenated with a "|" character. Will return an empty array if no
   *   target entity could be retrieved from the received field item value.
   */
  public function getTargetEntities(FieldItemInterface $item);

}
