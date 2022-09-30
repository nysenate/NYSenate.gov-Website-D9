<?php

/**
 * @file
 * Hooks and documentation related to password_policy module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter password policy showing status.
 *
 * @param bool $show_password_policy_status
 *   TRUE - password policy status element will be shown.
 * @param array $context
 *   An associative array containing some context elements. Depends on the place
 *   of parent function ("_password_policy_show_policy") execution.
 */
function hook_password_policy_show_policy_alter(&$show_password_policy_status, array $context) {
  if (isset($context['form_state']) && $context['form_state'] instanceof FormStateInterface) {
    $form_state = $context['form_state'];
    $form_build_info = $form_state->getBuildInfo();
    if (isset($form_build_info['form_id']) && $form_build_info['form_id'] == 'user_notifications_settings_form') {
      $show_password_policy_status = FALSE;
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
