<?php

namespace Drupal\nys_senators\Access;

use Drupal\block_content\BlockContentAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\nys_users\UsersHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Custom content block access control handler for MCPs.
 */
class McpBlockContentAccessControlHandler extends BlockContentAccessControlHandler {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * McpBlockContentAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EventDispatcherInterface $dispatcher,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($entity_type, $dispatcher);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Grant MCPs access to edit content blocks tied to their senator.
    $current_user = UsersHelper::resolveUser();
    if (UsersHelper::isMcp($current_user)) {
      $managed_senator_tids = UsersHelper::getManagedSenators($current_user);
      try {
        $node_storage = $this->entityTypeManager
          ->getStorage('node');
      }
      catch (\Exception) {
      }
      if (isset($node_storage)) {
        $is_block_linked_to_managed_senator = $node_storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('field_block', $entity->id(), 'CONTAINS')
          ->condition('field_senator_multiref', $managed_senator_tids, 'IN')
          ->count()
          ->execute();
        if ($is_block_linked_to_managed_senator) {
          return AccessResult::allowed();
        }
      }
    }

    // Otherwise, fallback on core access rules.
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Grant MCPs access to create new content blocks.
    $current_user = UsersHelper::resolveUser();
    if (UsersHelper::isMcp($current_user)) {
      return AccessResult::allowed();
    }

    // Otherwise, fallback on core access rules.
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
