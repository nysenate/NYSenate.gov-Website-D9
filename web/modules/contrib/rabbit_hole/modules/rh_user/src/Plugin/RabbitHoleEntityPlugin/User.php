<?php

namespace Drupal\rh_user\Plugin\RabbitHoleEntityPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for users.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_user",
 *  label = @Translation("User (deprecated)"),
 *  entityType = "user"
 * )
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
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
