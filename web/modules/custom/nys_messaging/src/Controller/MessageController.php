<?php

namespace Drupal\nys_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for nys_messaging module.
 */
class MessageController extends ControllerBase {

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The constructor class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current_user object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager'),
          $container->get('current_user')
      );
  }

  /**
   * Path to display the reply form.
   */
  public function reply($user_id, $private_message_id) {
    $content = $this->checkAccess($user_id);

    // Check if there's access.
    if (!empty($content)) {
      return $content;
    }

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
    $content = $this->checkAccess($user_id);

    // Check if there's access.
    if (!empty($content)) {
      return $content;
    }

    $content['forward_form'] = \Drupal::service('form_builder')->getForm('Drupal\nys_messaging\Form\ForwardForm', $user_id, $private_message_id);

    return $content;
  }

  /**
   * Path to display the Message Senator Form (Issue).
   */
  public function issue($user_id, $context, $issue_id) {

    $content['message_form'] = \Drupal::service('form_builder')->getForm('Drupal\nys_messaging\Form\SenatorMessageForm', $user_id, $context, $issue_id);

    return $content;
  }

  /**
   * Path to display the bulk message form.
   */
  public function bulkMessage($user_id, $recipient_uids) {
    // TO DO: Check if there's access.
    $content['bulk_message_form'] = \Drupal::service('form_builder')->getForm('Drupal\nys_messaging\Form\BulkMessageForm', $user_id, $recipient_uids);

    return $content;
  }

  /**
   * Method for checking the current user's access.
   */
  private function checkAccess($user_id) {
    $content = [];

    $current_user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());

    if ($user_id != $this->currentUser->id() && !in_array('legislative_correspondent', $current_user->getRoles())) {
      $content['error'] = [
        '#markup' => '<p>You dont have access to this message</p>',
      ];

      return $content;
    }

    if (in_array('legislative_correspondent', $current_user->getRoles()) && $user_id != $this->currentUser->id()) {
      if ($current_user->hasField('field_senator_inbox_access') && !$current_user->get('field_senator_inbox_access')->isEmpty()) {
        $senator = [];
        foreach ($current_user->field_senator_inbox_access as $senator_inbox_access) {
          $senator[] = $senator_inbox_access->entity;
        }

        if (!in_array($user_id, $senator)) {
          $content['error'] = [
            '#markup' => '<p>You dont have access to this message</p>',
          ];

          return $content;
        }
      }
      else {
        $content['error'] = [
          '#markup' => '<p>You dont have access to this message</p>',
        ];

        return $content;
      }

    }

    return $content;
  }

}
