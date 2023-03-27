<?php

namespace Drupal\comments_ban\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Validates the current user is not banned from the comments.
 */
class CommentsBanConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct for CommentsBanConstraintValidator.
   *
   * @param Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $this->entityTypeManager->getStorage('user')->load($current_user->id());
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    if ($this->currentUser->field_user_banned_comments->value) {
      $this->context->addViolation($constraint->userCommentBanned);
    }
  }

}
