<?php

namespace Drupal\rabbit_hole;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    BehaviorSettingsManager $rabbit_hole_behavior_settings_manager,
    RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler
  ) {
    $this->rhBehaviorSettingsManager = $rabbit_hole_behavior_settings_manager;
    $this->rhBehaviorPluginManager = $plugin_manager_rabbit_hole_behavior_plugin;
    $this->currentUser = $current_user;
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
      if (preg_match('/^entity\.(.+)\.canonical$/', $route, $matches)) {
        $entity = $request->get($matches[1]);
        if ($entity instanceof EntityInterface && $this->rhBehaviorSettingsManager->entityTypeIsEnabled($entity->getEntityTypeId())) {
          return $entity;
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
  public function getBehaviorPlugin(ContentEntityInterface $entity) {
    $values = $this->rhBehaviorSettingsManager->getEntityBehaviorSettings($entity);
    // Copy values with "rh_" prefixed-names to maintain existing installations.
    // @todo Remove this code later.
    foreach ($values as $property => $value) {
      $prefixed_property = "rh_$property";
      $values[$prefixed_property] = $value;
    }
    // Adding a note that prefixed values are deprecated.
    $values['deprecation_note'] = 'Prefixed values (rh_action, rh_redirect, etc.) are deprecated and will be removed in next versions. Use properties without "rh_" prefix.';

    // Perform the access check if bypass is not disabled.
    $values['bypass_access'] = FALSE;
    if (empty($values['no_bypass'])) {
      $permission = 'rabbit hole bypass ' . $entity->getEntityTypeId();
      $values['bypass_access'] = $this->currentUser->hasPermission($permission);
    }

    // Allow altering Rabbit Hole values.
    $this->moduleHandler->alter('rabbit_hole_values', $values, $entity);

    try {
      /** @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface $instance */
      $instance = $this->rhBehaviorPluginManager->createInstance($values['action'], $values);
    }
    catch (PluginException $e) {
      watchdog_exception('rabbit_hole', $e);
      return NULL;
    }

    // If configured, display a message explaining the Rabbit Hole is enabled.
    if ($values['bypass_access']) {
      // @todo Check whether plugin instance is suitable for the message instead
      // of checking particular ID. It could be some additional method in the
      // plugin interface.
      if ($values['bypass_message'] && $instance->getPluginId() !== 'display_page') {
        $message = t('This page is configured to apply "@action" Rabbit Hole action, but you have permission to see the page.', [
          '@action' => $instance->getPluginDefinition()['label'],
        ]);
        \Drupal::messenger()->addWarning($message);
      }
      return NULL;
    }
    return $instance;
  }

}
