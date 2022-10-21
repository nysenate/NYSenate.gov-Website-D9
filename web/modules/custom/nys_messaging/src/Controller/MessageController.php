<?php

namespace Drupal\nys_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for nys_messaging module.
 */
class MessageController extends ControllerBase {

  /**
   * Path to display the reply form.
   */
  public function reply($user_id, $private_message_id) {
    $content = [];

    $pm = \Drupal::entityTypeManager()->getStorage('private_message')
      ->load($private_message_id);

    $content['message'] = \Drupal::entityTypeManager()->getViewBuilder('private_message')
      ->view($pm, 'inbox');

    $content['reply_form'] = \Drupal::service('form_builder')->getForm('Drupal\nys_messaging\Form\ReplyForm', $user_id, $private_message_id);

    return $content;
  }

  /**
   * Path to display the reply form.
   */
  public function forward($user_id, $private_message_id) {
    $content = [];

    $content['forward_form'] = \Drupal::service('form_builder')->getForm('Drupal\nys_messaging\Form\ForwardForm', $user_id, $private_message_id);

    return $content;
  }

}
