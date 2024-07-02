<?php

namespace Drupal\rh_node\Plugin\RabbitHoleEntityPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for nodes.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_node",
 *  label = @Translation("Node (deprecated)"),
 *  entityType = "node"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
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
