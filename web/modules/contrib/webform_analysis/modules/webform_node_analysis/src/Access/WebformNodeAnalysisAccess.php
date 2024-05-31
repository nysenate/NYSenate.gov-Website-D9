<?php

namespace Drupal\webform_node_analysis\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\webform_node\Access\WebformNodeAccess;

/**
 * Defines the custom access control handler for the webform node analysis.
 */
class WebformNodeAnalysisAccess {

  /**
   * Check whether the user can access a node's webform results analysis.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param string $field_name
   *   The webform field name.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformNodeAnalysisAccess($operation, $entity_access, NodeInterface $node, $field_name, AccountInterface $account) {
    $access_result = WebformNodeAccess::checkWebformResultsAccess($operation, $entity_access, $node, $account);
    if ($access_result->isAllowed()) {
      if ($node->hasField($field_name)) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    else {
      return $access_result;
    }
  }

}
