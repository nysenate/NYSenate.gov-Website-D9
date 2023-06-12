<?php

namespace Drupal\rh_user\Plugin\RabbitHoleEntityPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for users.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_user",
 *  label = @Translation("User"),
 *  entityType = "user"
 * )
 */
class User extends RabbitHoleEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getGlobalConfigFormId() {
    return "user_admin_settings";
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalFormSubmitHandlerAttachLocations(array $form, FormStateInterface $form_state) {
    return ['#submit'];
  }

}
