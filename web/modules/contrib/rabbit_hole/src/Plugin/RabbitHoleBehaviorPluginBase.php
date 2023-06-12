<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Rabbit hole behavior plugin plugins.
 */
abstract class RabbitHoleBehaviorPluginBase extends PluginBase implements RabbitHoleBehaviorPluginInterface {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity) {
    // Perform no action.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(
    array &$form,
    FormStateInterface $form_state,
    $form_id,
    EntityInterface $entity = NULL,
    $entity_is_bundle = FALSE,
    ImmutableConfig $bundle_settings = NULL
  ) {
    // Present no settings form.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormHandleSubmit(&$form, &$form_state) {
    // No extra action to handle submission by default.
  }

  /**
   * {@inheritdoc}
   */
  public function alterExtraFields(array &$fields) {
    // Don't change the fields by default.
  }

  /**
   * {@inheritdoc}
   */
  public function usesResponse() {
    return RabbitHoleBehaviorPluginInterface::USES_RESPONSE_NEVER;
  }

  /**
   * Returns configuration object with "Rabbit Hole" bundle settings.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the action is being performed on.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Configuration object with bundle settings.
   */
  protected function getBundleSettings(EntityInterface $entity) {
    $bundle_entity_type = $entity->getEntityType()->getBundleEntityType();
    return \Drupal::service('rabbit_hole.behavior_settings_manager')
      ->loadBehaviorSettingsAsConfig(
        $bundle_entity_type ?: $entity->getEntityType()->id(),
        $bundle_entity_type ? $entity->bundle() : NULL);
  }

  /**
   * Returns the fallback action in case if action cannot be performed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the action is being performed on.
   *
   * @return string
   *   Fallback action name.
   */
  protected function getFallbackAction(EntityInterface $entity) {
    return 'access_denied';
  }

}
