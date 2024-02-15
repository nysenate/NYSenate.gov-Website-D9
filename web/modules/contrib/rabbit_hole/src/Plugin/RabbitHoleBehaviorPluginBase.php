<?php

namespace Drupal\rabbit_hole\Plugin;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0.
   *   There is no need for additional fields, as all configuration is stored in
   *   a single serialized field.
   *
   * @see https://www.drupal.org/node/3359194
   */
  public function alterExtraFields(array &$fields) {
  }

  /**
   * {@inheritdoc}
   */
  public function usesResponse() {
    return RabbitHoleBehaviorPluginInterface::USES_RESPONSE_NEVER;
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
