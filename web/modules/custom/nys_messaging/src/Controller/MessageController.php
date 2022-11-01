<?php

namespace Drupal\nys_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
      if ($current_user->hasField('field_senator_management') && !$current_user->get('field_senator_management')->isEmpty()) {
        // @phpstan-ignore-next-line
        $senator = $current_user->field_senator_management->first()->entity;

        if ($user_id != $senator->field_user_account->target_id) {
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
