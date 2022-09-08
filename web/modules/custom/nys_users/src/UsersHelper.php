<?php

namespace Drupal\nys_users;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper class for nys_users module.
 */
class UsersHelper {

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Default object for database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManagerInterface;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user, Connection $connection, EntityTypeManagerInterface $entityTypeManagerInterface) {
    $this->currentUser = $current_user;
    $this->connection = $connection;
    $this->entityTypeManagerInterface = $entityTypeManagerInterface;
  }

  /**
   * Check if a user is out of state.
   *
   * @param mixed $user
   *   User object or user id.
   *
   * @return bool
   *   whether given user is an out of state user.
   */
  public function isOutOfState($user) {
    $uid = '';
    if (is_object($user)) {
      $uid = $user->id();
    }
    elseif (is_numeric($user)) {
      $uid = $user;
    }
    elseif (empty($user->uid)) {
      $uid = $this->currentUser->id();
    }

    $district_tid = $this->getDistrictTid($uid);

    if (empty($district_tid)) {
      return TRUE;
    }
    else {
      // Check if user's address is out of state.
      $user = $this->entityTypeManagerInterface->getStorage('user')
        ->load($uid);

      if ($user->hasField('field_address') && !$user->get('field_address')->isEmpty()) {
        $address = $user->get('field_address')->getValue();
        return $address[0]['administrative_area'] != 'NY' ? TRUE : FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Check if user is a senator.
   *
   * @param object|int $user
   *   User object or user id.
   *
   * @return bool
   *   Whether given user refers to a user who is a Senator.
   */
  public function isSenator($user) {
    $uid = '';
    if (is_object($user)) {
      $uid = $user->uid;
    }
    elseif (is_numeric($user)) {
      $uid = $user;
    }
    elseif (empty($user->uid)) {
      $uid = $this->currentUser->id();
    }

    if (!is_object($user)) {
      $user = $this->entityTypeManagerInterface->getStorage('user')
        ->load($uid);
    }
    if ($user->hasRole('senator')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Given a $user object or $uid, return that user's district's tid.
   *
   * @param int|object $user
   *   User object or user id.
   *
   * @return int
   *   District taxonomy term id attached to the user object
   */
  public function getDistrictTid($user) {

    $data = $this->getDistrictSenatorDataArray($user);

    if (!empty($data['district_tid'])) {
      return $data['district_tid'];
    }
    return NULL;
  }

  /**
   * Get user's district and senator identifiers.
   *
   * Given a $user object or $uid, return that user's district
   * and that district's senator's identifiers, used
   * by other utility functions in this module,
   * as not to repeat code.
   *
   * @todo This function is inappropriately named.
   * It does not actually load the district info from the user,
   * but rather loads the user's senator, then loads
   * the district from the senator object. This causes a
   * failure if the district does not have an assigned senator (e.g., an
   * empty seat).  We are leaving this in place for now because it is already
   * in wide usage.  See user_get_district_data() to load the district info
   * properly from the user information.
   *
   * @param object|int $user
   *   Object or $uid, if $uid will convert to $user object.
   *
   * @return array
   *   An array containing senator_uid, senator_nid and district_tid.
   */
  public function getDistrictSenatorDataArray($user) {
    $user_district_senator_info = &drupal_static(__FUNCTION__);
    $uid = '';
    if (is_object($user)) {
      $uid = $user->uid;
    }
    elseif (is_numeric($user)) {
      $uid = $user;
    }
    elseif (empty($user->uid)) {
      $uid = $this->currentUser->id();
    }

    $senator = NULL;
    $user = $this->entityTypeManagerInterface->getStorage('user')
      ->load($uid);

    if (!isset($user_district_senator_info[$uid])) {
      if ($this->isSenator($uid)) {
        // Assign the current user as the senator.
        $senator = $user;
      }
      else {
        if ($user->hasField('field_senator_multiref') && !$user->get('field_senator_multiref')->isEmpty()) {
          $tid = $user->get('field_senator_multiref')->first()->getString() ?? '';
          if (!empty($tid)) {
            $senator = $this->entityTypeManagerInterface->getStorage('taxonomy_term')
              ->load($tid);
          }
        }
      }

      if (!empty($senator)) {
        $query = "SELECT fs.entity_id as district_tid FROM taxonomy_term__field_senator fs WHERE fs.field_senator_target_id = :sid AND fs.bundle = :bundle";
        $result = $this->connection->query($query, [
          ':sid' => $senator->id(),
          ':bundle' => 'districts',
        ])->fetchAssoc();

        if (!empty($result)) {
          return $user_district_senator_info[$uid] = $result;
        }
      }

      // For anonymous users, return NULL
      // if there's no senator.
      else {
        return NULL;
      }
    }

    return $user_district_senator_info[$uid];
  }

}
