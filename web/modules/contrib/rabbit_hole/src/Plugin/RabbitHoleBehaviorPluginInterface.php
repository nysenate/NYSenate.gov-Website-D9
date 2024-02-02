<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Rabbit hole behavior plugin plugins.
 */
interface RabbitHoleBehaviorPluginInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurableInterface {

  const USES_RESPONSE_NEVER = 0;
  const USES_RESPONSE_SOMETIMES = 1;
  const USES_RESPONSE_ALWAYS = 2;

  /**
   * Perform the rabbit hole action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the action is being performed on.
   */
  public function performAction(EntityInterface $entity);

  /**
   * Get whether this plugin uses a response to perform its action.
   *
   * Override this to return one of USES_RESPONSE_NEVER,
   * USES_RESPONSE_SOMETIMES, or USES_RESPONSE_ALWAYS to indicate whether
   * performAction() should be invoked only when a null response is given,
   * regardless of whether there is a response (it'll figure out what to do with
   * or without on its own), or only when a non-null response is given. Defaults
   * to returning USES_RESPONSE_NEVER.
   */
  public function usesResponse();

}
