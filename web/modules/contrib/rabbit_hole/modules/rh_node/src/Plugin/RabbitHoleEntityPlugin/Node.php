<?php

namespace Drupal\rh_node\Plugin\RabbitHoleEntityPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for nodes.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_node",
 *  label = @Translation("Node"),
 *  entityType = "node"
 * )
 */
class Node extends RabbitHoleEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBundleFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state) {
    if (\Drupal::moduleHandler()->moduleExists('field_ui') && $form_state->getFormObject()->getEntity()->isNew()) {
      return [['actions', 'save_continue', '#submit']];
    }
    return parent::getBundleFormSubmitHandlerAttachLocations($form, $form_state);
  }

}
