<?php

namespace Drupal\nys_senators\Access;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\nys_users\UsersHelper;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines access check for an LC/MCP managing a senator.
 */
class SenatorAccessCheck implements AccessInterface {

  /**
   * Drupal Current Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $currentRoute;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(RouteMatchInterface $currentRoute, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentRoute = $currentRoute;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Loads a user from storage.  Returns a new user if loading fails.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUser(AccountInterface $account): EntityInterface {
    $storage = $this->entityTypeManager->getStorage('user');
    return $storage->load($account->id()) ?? $storage->create();
  }

  /**
   * Determines if a user has access to a senator's management pages.
   *
   * This requires that the current route has a parameter named 'taxonomy_term',
   * which must be a taxonomy_term entity.
   *
   * @see taxonomy.links.task.yml:entity.taxonomy_term.canonical
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $ret = new AccessResultForbidden('Could not confirm access');
    $senator = $this->currentRoute->getParameter('taxonomy_term') ?? NULL;
    if ($senator instanceof Term) {
      try {
        /**
         * @var \Drupal\user\Entity\User $user
         */
        $user = $this->getUser($account);
        $is_manager = UsersHelper::isLcOrMcp($user);
        $assigns = array_merge(
              array_column($user->get('field_senator_multiref')->getValue() ?? [], 'target_id'),
              array_column($user->get('field_senator_inbox_access')->getValue() ?? [], 'target_id')
          );
        if ($is_manager && in_array($senator->id(), $assigns)) {
          $ret = new AccessResultAllowed();
        }
        else {
          $ret->setReason('User has no access');
        }
      }
      catch (\Throwable $e) {
        $ret->setReason('Could not confirm user access');
      }
    }
    return $ret;
  }

}
