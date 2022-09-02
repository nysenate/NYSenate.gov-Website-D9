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
  public function isOutOfState($user = '') {
    if (is_object($user)) {
      $uid = $user->id();
    }
    elseif (is_numeric($user)) {
      $uid = $user;
    }
    elseif (empty($user->uid)) {
      $uid = $this->currentUser->id();
    }

    $district_tid = user_get_district_tid($uid);

    if (empty($district_tid)) {
      return TRUE;
    }
    else {
      // Check if user's address is out of state.
      $query = "SELECT 1 FROM field_data_field_address fa
        JOIN location l on l.lid = fa.field_address_lid
        WHERE fa.entity_id = :uid AND fa.bundle = 'user' AND l.province != 'NY' LIMIT 1";
      $oos = (int) db_query($query, array(':uid' => $uid))->FetchField();
      return $oos;
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
  public function isSenator() {
    if (is_object($user)) {
      $uid = $user->uid;
    }
    elseif (is_numeric($user)) {
      $uid = $user;
    }
    elseif (empty($user->uid)) {
      $uid = $this->currentUser()->id();
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
   * @param $user user object or user id
   *
   * @return int district taxonomy term id attached to the user object
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
   * Given a $user object or $uid, return that user's district and that district's
   * senator's identifiers, used by other utility functions in this module,
   * as not to repeat code.
   *
   * TODO: This function is inappropriately named.
   * It does not actually load the district info from the user, but rather loads
   * the user's senator, then loads the district from the senator object.  This
   * causes a failure if the district does not have an assigned senator (e.g., an
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
    if (is_object($user)) {
      $uid = $user->uid;
    }
    elseif (is_numeric($user)) {
      $uid = $user;
    }
    elseif (empty($user->uid)) {
      $uid = $this->currentUser()->id();
    }

    if (!isset($user_district_senator_info[$uid])) {
      if ($this->isSenator($uid)) {
        $query = "SELECT ua.field_user_account_target_id AS senator_uid,
          ua.entity_id AS senator_nid, s.entity_id AS district_tid
          FROM field_data_field_user_account ua
          JOIN field_data_field_senator s ON s.field_senator_target_id = ua.entity_id AND s.bundle = 'districts'
          WHERE ua.field_user_account_target_id = :uid;";

        $user_district_senator_info[$uid] = $this->connection->query($query, [':uid' => $uid])->fetchAssoc();
      }
      else {
        $query = "SELECT ua.field_user_account_target_id AS senator_uid,
          s.field_senator_target_id AS senator_nid,
          fd.field_district_target_id AS district_tid
          FROM field_data_field_district fd
          JOIN field_data_field_senator s ON s.entity_id = fd.field_district_target_id
          JOIN field_data_field_user_account ua ON ua.entity_id = s.field_senator_target_id
          WHERE fd.entity_id = :uid and fd.bundle = 'user' and s.bundle = 'districts';";

        $user_district_senator_info[$uid] = $this->connection->query($query, [':uid' => $uid])->fetchAssoc();
      }
    }
    return $user_district_senator_info[$uid];
  }
}
