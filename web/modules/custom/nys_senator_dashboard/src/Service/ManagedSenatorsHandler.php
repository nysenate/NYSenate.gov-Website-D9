<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\TempStoreException;

/**
 * Provides service methods for managing an MCP's or LC's senator(s).
 */
class ManagedSenatorsHandler {

  use StringTranslationTrait;

  /**
   * The private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the ManagedSenatorsHandler service.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temp store factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory, MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->tempStoreFactory = $tempStoreFactory->get('nys_senator_dashboard');
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets a given user's managed senators.
   *
   * @param int $user_id
   *   The user's ID.
   * @param bool $tids_only
   *   Whether to return just the senator TIDs instead of the full entities.
   *
   * @return array
   *   An array of senator term entities managed by the user.
   */
  public function getManagedSenators(int $user_id, bool $tids_only = FALSE): array {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    }
    catch (\Throwable) {
      return [];
    }
    if (!isset($user) && !$user->hasField('field_senator_multiref')) {
      return [];
    }
    return $tids_only
      ? array_column($user->field_senator_multiref->getValue(), 'target_id')
      : $user->field_senator_multiref->referencedEntities();
  }

  /**
   * Gets (and sets, if unset) the current user's active managed senator.
   *
   * @return int|null
   *   The senator TID if successful, NULL otherwise.
   */
  public function getOrSetActiveSenator(int $user_id): ?int {
    // Get active senator TID.
    $active_senator_tid = $this->tempStoreFactory->get('active_managed_senator_tid');
    if ($active_senator_tid) {
      return $active_senator_tid;
    }

    // If unset, set first senator in reference field to active.
    $managed_senators = $this->getManagedSenators($user_id);
    if (count($managed_senators) > 0) {
      try {
        $this->tempStoreFactory->set('active_managed_senator_tid', $managed_senators[0]->id());
      }
      catch (TempStoreException) {
        return NULL;
      }
      Cache::invalidateTags(['tempstore_user:' . $user_id]);
      $active_senator_tid = $managed_senators[0]->id();
    }

    return $active_senator_tid;
  }

  /**
   * Updates the active managed senator for a given user.
   *
   * @param int $user_id
   *   The user ID.
   * @param int $senator_id
   *   The senator term ID.
   * @param bool $include_message
   *   Whether to print a status message for the user.
   *
   * @return bool
   *   Indicates if operation was a success.
   */
  public function updateActiveSenator(int $user_id, int $senator_id, bool $include_message = TRUE): bool {
    // Update the active managed senator if allowed.
    $allowed_senator_ids = $this->getManagedSenators($user_id, TRUE);
    if (in_array($senator_id, $allowed_senator_ids)) {
      try {
        $this->tempStoreFactory->set('active_managed_senator_tid', $senator_id);
        if ($include_message) {
          $this->messenger->addMessage($this->t('Your active managed senator has been updated.'));
        }
        Cache::invalidateTags(['tempstore_user:' . $user_id]);
        return TRUE;
      }
      catch (\Exception) {
        if ($include_message) {
          $this->messenger->addError($this->t('There was an error updating your active managed senator.'));
        }
        return FALSE;
      }
    }
    else {
      if ($include_message) {
        $this->messenger->addError($this->t('The specified senator ID is invalid or you do not have access to manage this senator.'));
      }
      return FALSE;
    }
  }

}
