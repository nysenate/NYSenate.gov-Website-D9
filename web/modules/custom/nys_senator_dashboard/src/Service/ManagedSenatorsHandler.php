<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\nys_senators\SenatorsHelper;

/**
 * Provides service methods for managing an MCP's or LC's senator(s).
 */
class ManagedSenatorsHandler {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

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
   * The senators helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $senatorsHelper;

  /**
   * Constructs the ManagedSenatorsHandler service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temp store factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\nys_senators\SenatorsHelper $senators_helper
   *   The senators helper service.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    PrivateTempStoreFactory $tempStoreFactory,
    MessengerInterface $messenger,
    EntityTypeManagerInterface $entityTypeManager,
    SenatorsHelper $senators_helper,
  ) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $tempStoreFactory->get('nys_senator_dashboard');
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
    $this->senatorsHelper = $senators_helper;
  }

  /**
   * Gets the current user's managed senators.
   *
   * @param bool $tids_only
   *   Whether to return just the senator TIDs instead of the full entities.
   *
   * @return array
   *   An array of senator term entities managed by the user.
   */
  public function getManagedSenators(bool $tids_only = TRUE): array {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
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
   * @param bool $tid_only
   *   Whether to return TIDs or entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface|int|null
   *   The senator entity or TID if successful, NULL otherwise.
   */
  public function getActiveSenator(bool $tid_only = TRUE): EntityInterface|int|null {
    // Get active senator TID.
    $active_senator_tid = $this->tempStoreFactory->get('active_managed_senator_tid');

    // If unset, set first senator in reference field to active.
    if (empty($active_senator_tid)) {
      $managed_senators = $this->getManagedSenators(FALSE);
      if (count($managed_senators) > 0) {
        try {
          $this->tempStoreFactory->set('active_managed_senator_tid', $managed_senators[0]->id());
        }
        catch (TempStoreException) {
          return NULL;
        }
        Cache::invalidateTags(['tempstore_user:' . $this->currentUser->id()]);
        $active_senator = $managed_senators[0];
        $active_senator_tid = $active_senator->id();
      }
    }

    if ($tid_only) {
      return $active_senator_tid;
    }
    elseif (!empty($active_senator)) {
      return $active_senator;
    }
    else {
      try {
        $active_senator = $this->entityTypeManager->getStorage('taxonomy_term')
          ->load($active_senator_tid);
      }
      catch (\Exception) {
        return NULL;
      }
      return $active_senator;
    }
  }

  /**
   * Updates the current user's active managed senator.
   *
   * @param int $senator_id
   *   The senator term ID.
   * @param bool $include_message
   *   Whether to print a status message for the user.
   *
   * @return bool
   *   Indicates if operation was a success.
   */
  public function updateActiveSenator(int $senator_id, bool $include_message = TRUE): bool {
    // Update the active managed senator if allowed.
    $allowed_senator_ids = $this->getManagedSenators();
    if (in_array($senator_id, $allowed_senator_ids)) {
      try {
        $this->tempStoreFactory->set('active_managed_senator_tid', $senator_id);
        if ($include_message) {
          $this->messenger->addMessage($this->t('Your active managed senator has been updated.'));
        }
        Cache::invalidateTags(['tempstore_user:' . $this->currentUser->id()]);
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

  /**
   * Gets the current user's active senator's homepage URL.
   *
   * @return string
   *   The homepage URL, or an empty string on error.
   */
  public function getActiveSenatorHomepageUrl(): string {
    $senator = $this->getActiveSenator(FALSE);
    return !empty($senator) ? $this->senatorsHelper->getMicrositeUrl($senator) : '';
  }

  /**
   * Gets the current user's active senator's district ID.
   *
   * @return int|null
   *   The district TID.
   */
  public function getActiveSenatorDistrictId(): int|null {
    $senator = $this->getActiveSenator(FALSE);
    return !empty($senator) ? $this->senatorsHelper->loadDistrict($senator)?->id() : NULL;
  }

}
