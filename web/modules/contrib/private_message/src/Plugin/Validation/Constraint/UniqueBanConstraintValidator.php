<?php

namespace Drupal\private_message\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\private_message\Entity\PrivateMessageBan;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for a unique bans.
 */
class UniqueBanConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new UniqueBanConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    assert($entity instanceof PrivateMessageBan);

    $storage = $this->entityTypeManager->getStorage('private_message_ban');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('owner', $entity->getOwnerId(), '=')
      ->condition('target', $entity->getTargetId(), '=');

    if ($query->range(0, 1)->execute()) {
      $this->context->buildViolation($constraint->message, [
        '%user' => $entity->getTarget()->getDisplayName(),
      ])->addViolation();
    }
  }

}
