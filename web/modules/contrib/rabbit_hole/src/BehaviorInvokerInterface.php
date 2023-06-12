<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Defines an interface for behavior invoker service.
 */
interface BehaviorInvokerInterface {

  /**
   * Retrieves entity to apply rabbit hole behavior from event object.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The kernel request event.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|false
   *   Entity object if the Rabbit Hole action is applicable or FALSE otherwise.
   */
  public function getEntity(KernelEvent $event);

  /**
   * Get the behavior plugin for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to apply rabbit hole behavior on.
   *
   * @return \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase|null
   *   Rabbit Hole action plugin or NULL.
   */
  public function getBehaviorPlugin(ContentEntityInterface $entity);

  /**
   * Invoke a rabbit hole behavior based on an entity's configuration.
   *
   * This assumes the entity is configured for use with Rabbit Hole - if you
   * pass an entity to this method and it does not have a rabbit hole plugin it
   * will use the defaults!
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to apply rabbit hole behavior on.
   * @param \Symfony\Component\HttpFoundation\Response $current_response
   *   The current response, to be passed along to and potentially altered by
   *   any called rabbit hole plugin.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   A response or NULL if the response is unchanged.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function processEntity(ContentEntityInterface $entity, Response $current_response = NULL);

  /**
   * Load a list of entity IDs supported by rabbit hole given available plugins.
   *
   * @return array
   *   An array of string entity ids.
   */
  public function getPossibleEntityTypeKeys();

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
  public function getRabbitHoleValuesForEntity(ContentEntityInterface $entity);

  /**
   * An entity type's rabbit hole config, or the default if it does not exist.
   *
   * Return an entity types's rabbit hole configuration or, failing that, the
   * base default configuration.
   *
   * @return array|false
   *   An array of values from the entity's fields matching the base properties
   *   added by rabbit hole. Explicit false if no matching configuration was
   *   found.
   */
  public function getRabbitHoleValuesForEntityType($entity_type_id, $bundle_id = NULL);

}
