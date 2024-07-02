<?php

namespace Drupal\rabbit_hole\Plugin;

@trigger_error('The ' . __NAMESPACE__ . '\RabbitHoleEntityPluginBase is deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. See https://www.drupal.org/node/3359194', E_USER_DEPRECATED);

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Rabbit hole entity plugin plugins.
 */
abstract class RabbitHoleEntityPluginBase extends PluginBase implements RabbitHoleEntityPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state) {
    return [['actions', 'submit', '#submit']];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state) {
    return [['actions', 'submit', '#submit']];
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalConfigFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state) {
    return [['actions', 'submit', '#submit']];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTokenMap() {
    $map = [];
    $map[$this->pluginDefinition['entityType']] = $this->pluginDefinition['entityType'];
    $bundle = \Drupal::entityTypeManager()
      ->getDefinition($this->pluginDefinition['entityType'])
      ->getBundleEntityType();
    if (!empty($bundle)) {
      $map[$bundle] = $bundle;
    }
    return $map;
  }

}
