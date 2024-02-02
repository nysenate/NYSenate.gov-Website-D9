<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface BehaviourSettingsManagerInterface.
 */
interface BehaviorSettingsManagerInterface {

  /**
   * Save behavior settings for an entity or bundle.
   *
   * @param array $settings
   *   The settings for the BehaviorSettings entity.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string|null $bundle
   *   The bundle name.
   *
   * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Use
   *   \Drupal\rabbit_hole\Entity\BehaviorSettings::loadByEntityTypeBundle() and
   *   \Drupal\rabbit_hole\Entity\BehaviorSettings::save().
   *
   * @see https://www.drupal.org/node/3376049
   */
  public function saveBehaviorSettings(array $settings, string $entity_type_id, ?string $bundle): void;

  /**
   * Get behavior settings for the given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID (e.g. node, user).
   * @param string $bundle
   *   The entity bundle name.
   *
   * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Use
   *   \Drupal\rabbit_hole\Entity\BehaviorSettings::loadByEntityTypeBundle().
   *
   * @see https://www.drupal.org/node/3376049
   */
  public function getBehaviorSettings(string $entity_type_id, string $bundle): array;

  /**
   * An entity's rabbit hole configuration, or the default if it does not exist.
   *
   * Return an entity's rabbit hole configuration or, failing that, the default
   * configuration for the bundle (which itself will call the base default
   * configuration if necessary).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to apply rabbit hole behavior on.
   *
   * @return array
   *   An array of values from the entity's fields matching the base properties
   *   added by rabbit hole.
   */
  public function getEntityBehaviorSettings(ContentEntityInterface $entity): array;

  /**
   * Checks if an entity type is enabled in the Rabbit Hole settings.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return bool
   *   TRUE if an entity type is enabled, FALSE otherwise.
   */
  public function entityTypeIsEnabled(string $entity_type_id): bool;

  /**
   * Enables Rabbit Hole support for an entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   */
  public function enableEntityType(string $entity_type_id): void;

  /**
   * Disables Rabbit Hole support for an entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   */
  public function disableEntityType(string $entity_type_id): void;

}
