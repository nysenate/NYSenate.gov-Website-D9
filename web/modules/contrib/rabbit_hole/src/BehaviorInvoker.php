<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Default implementation of Rabbit Hole behaviors invoker.
 */
class BehaviorInvoker implements BehaviorInvokerInterface {

  /**
   * Behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManager
   */
  protected $rhBehaviorSettingsManager;

  /**
   * Behavior plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
   */
  protected $rhBehaviorPluginManager;

  /**
   * Entity plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
   */
  protected $rhEntityPluginManager;

  /**
   * Entity extender service.
   *
   * @var \Drupal\rabbit_hole\EntityExtender
   */
  protected $rhEntityExtender;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * BehaviorInvoker constructor.
   *
   * @param \Drupal\rabbit_hole\BehaviorSettingsManager $rabbit_hole_behavior_settings_manager
   *   Behavior settings manager.
   * @param \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin
   *   Behavior plugin manager.
   * @param \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager $plugin_manager_rabbit_hole_entity_plugin
   *   Entity plugin manager.
   * @param \Drupal\rabbit_hole\EntityExtender $entity_extender
   *   Entity extender service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    BehaviorSettingsManager $rabbit_hole_behavior_settings_manager,
    RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin,
    RabbitHoleEntityPluginManager $plugin_manager_rabbit_hole_entity_plugin,
    EntityExtender $entity_extender,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler = NULL
  ) {
    $this->rhBehaviorSettingsManager = $rabbit_hole_behavior_settings_manager;
    $this->rhBehaviorPluginManager = $plugin_manager_rabbit_hole_behavior_plugin;
    $this->rhEntityPluginManager = $plugin_manager_rabbit_hole_entity_plugin;
    $this->rhEntityExtender = $entity_extender;
    $this->currentUser = $current_user;
    if (!$module_handler) {
      @trigger_error('This module handler workaround is deprecated in rabbit_hole:8.x-1.0 version and will be removed in rabbit_hole:2.x-2.0. The module_handler service must be passed to ' . __NAMESPACE__ . '\BehaviorInvoker::__construct(). See https://www.drupal.org/project/rabbit_hole/issues/3232505', E_USER_DEPRECATED);
      $module_handler = \Drupal::moduleHandler();
    }
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(KernelEvent $event) {
    $request = $event->getRequest();
    // Don't process events with HTTP exceptions - those have either been thrown
    // by us or have nothing to do with rabbit hole.
    if ($request->get('exception') != NULL) {
      return FALSE;
    }

    // Get the route from the request.
    if ($route = $request->get('_route')) {
      // Only continue if the request route is the an entity canonical.
      if (preg_match('/^entity\.(.+)\.canonical$/', $route)) {
        // We check for all of our known entity keys that work with rabbit hole
        // and invoke rabbit hole behavior on the first one we find (which
        // should also be the only one).
        $entity_keys = $this->getPossibleEntityTypeKeys();
        foreach ($entity_keys as $ekey) {
          $entity = $request->get($ekey);
          if (isset($entity) && $entity instanceof ContentEntityInterface) {
            return $entity;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function processEntity(ContentEntityInterface $entity, Response $current_response = NULL) {
    $plugin = $this->getBehaviorPlugin($entity);

    if ($plugin === NULL) {
      return NULL;
    }

    $resp_use = $plugin->usesResponse();
    $response_required = $resp_use == RabbitHoleBehaviorPluginInterface::USES_RESPONSE_ALWAYS;
    $response_allowed = $resp_use == $response_required
      || $resp_use == RabbitHoleBehaviorPluginInterface::USES_RESPONSE_SOMETIMES;

    // Most plugins never make use of the response and only run when it's not
    // provided (i.e. on a request event).
    if ((!$response_allowed && $current_response == NULL)
      // Some plugins may or may not make use of the response so they'll run in
      // both cases and work out the logic of when to return NULL internally.
      || $response_allowed
      // Though none exist at the time of this writing, some plugins could
      // require a response so that case is handled.
      || $response_required && $current_response != NULL) {

      $response = $plugin->performAction($entity, $current_response);

      // Execute a fallback action until we have correct response object.
      // It allows us to have a chain of fallback actions until we execute the
      // final one.
      while (!$response instanceof Response && \is_string($response) && $this->rhBehaviorPluginManager->getDefinition($response, FALSE) !== NULL) {
        $fallback_plugin = $this->rhBehaviorPluginManager->createInstance($response, []);
        $response = $fallback_plugin->performAction($entity, $current_response);
      }

      // Alter the response before it is returned.
      $this->moduleHandler->alter('rabbit_hole_response', $response, $entity);

      return $response;
    }
    // All other cases return NULL, meaning the response is unchanged.
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleEntityTypeKeys() {
    $entity_type_keys = [];
    foreach ($this->rhEntityPluginManager->getDefinitions() as $def) {
      $entity_type_keys[] = $def['entityType'];
    }
    return $entity_type_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getRabbitHoleValuesForEntity(ContentEntityInterface $entity) {
    $field_keys = array_keys($this->rhEntityExtender->getGeneralExtraFields());
    $values = [];

    $config = $this->rhBehaviorSettingsManager->loadBehaviorSettingsAsConfig(
      $entity->getEntityType()->getBundleEntityType()
        ?: $entity->getEntityType()->id(),
      $entity->getEntityType()->getBundleEntityType()
        ? $entity->bundle()
        : NULL
    );

    // We trigger the default bundle action under the following circumstances:
    $trigger_default_bundle_action =
    // Bundle settings do not allow override.
      !$config->get('allow_override')
    // Entity does not have rh_action field.
      || !$entity->hasField('rh_action')
    // Entity has rh_action field but it's null (hasn't been set).
      || $entity->get('rh_action')->value == NULL
    // Entity has been explicitly set to use the default bundle action.
      || $entity->get('rh_action')->value == 'bundle_default';

    if ($trigger_default_bundle_action) {
      foreach ($field_keys as $field_key) {
        $config_field_key = substr($field_key, 3);
        $values[$field_key] = $config->get($config_field_key);
      }
    }
    else {
      foreach ($field_keys as $field_key) {
        if ($entity->hasField($field_key)) {
          $values[$field_key] = $entity->{$field_key}->value;
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getRabbitHoleValuesForEntityType($entity_type_id, $bundle_id = NULL) {
    $field_keys = array_keys($this->rhEntityExtender->getGeneralExtraFields());
    $values = [];

    $config = $this->rhBehaviorSettingsManager->loadBehaviorSettingsAsConfig($entity_type_id, $bundle_id);
    foreach ($field_keys as $field_key) {
      $config_field_key = substr($field_key, 3);
      $values[$field_key] = $config->get($config_field_key);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugin(ContentEntityInterface $entity) {
    $values = $this->getRabbitHoleValuesForEntity($entity);
    $permission = 'rabbit hole bypass ' . $entity->getEntityTypeId();
    $values['bypass_access'] = $this->currentUser->hasPermission($permission);

    // Allow altering Rabbit Hole values.
    $this->moduleHandler->alter('rabbit_hole_values', $values, $entity);

    // Do nothing if action is missing or access is bypassed.
    if (empty($values['rh_action']) || $values['bypass_access']) {
      return NULL;
    }

    return $this->rhBehaviorPluginManager->createInstance($values['rh_action'], $values);
  }

}
