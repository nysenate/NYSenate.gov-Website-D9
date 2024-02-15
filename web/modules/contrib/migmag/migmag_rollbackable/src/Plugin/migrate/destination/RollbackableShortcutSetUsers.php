<?php

declare(strict_types = 1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\migmag_rollbackable\Traits\RollbackableConnectionTrait;
use Drupal\migmag_rollbackable\Traits\RollbackableDataTrait;
use Drupal\migrate\Row;
use Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers;
use Drupal\user\Entity\User;

/**
 * Rollbackable user shortcut set destination plugin.
 *
 * @see \Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_shortcut_set_users",
 *   provider = "shortcut"
 * )
 */
final class RollbackableShortcutSetUsers extends ShortcutSetUsers {

  use RollbackableConnectionTrait;
  use RollbackableDataTrait;

  /**
   * Prefix for the target object ID (which isn't a target object).
   *
   * @const string
   */
  const TARGET_ID_PREFIX = 'user-shortcut-set-';

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $account = User::load($row->getDestinationProperty('uid'));
    $previous_value = shortcut_current_displayed_set($account)->id();

    $destination_ids = parent::import($row, $old_destination_id_values);

    $target_id = implode(self::DERIVATIVE_SEPARATOR, [
      self::TARGET_ID_PREFIX,
      $destination_ids[1],
    ]);

    $this->saveTargetRollbackData($target_id, $previous_value, '', '');

    return $destination_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $account = User::load($destination_identifier['uid']);
    $target_id = implode(self::DERIVATIVE_SEPARATOR, [
      self::TARGET_ID_PREFIX,
      $destination_identifier['uid'],
    ]);
    $data = $this->getTargetRollbackData($target_id, '', '');

    if (
      $data &&
      $shortcut_set = $this->shortcutSetStorage->load($data)
    ) {
      $this->shortcutSetStorage->assignUser($shortcut_set, $account);
    }
    else {
      $this->shortcutSetStorage->unassignUser($account);
    }

    $this->deleteTargetRollbackData($target_id, '', '');
  }

}
