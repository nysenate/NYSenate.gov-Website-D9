<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides Senator Dashboard management service methods.
 */
class SenatorDashboardManager {

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
   * Constructs the SenatorDashboardManager service.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temp store factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory, MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets the current user's active senator ID.
   *
   * @return int|null
   *   The senator TID if set, NULL otherwise.
   */
  public function getActiveSenatorForCurrentUser(): ?int {
    $tempstore = $this->tempStoreFactory->get('nys_senator_dashboard');
    return $tempstore->get('active_managed_senator');
  }

  /**
   * Sets the active managed senator for the given user.
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
  public function setActiveSenatorForUserId(int $user_id, int $senator_id, bool $include_message = TRUE): bool {
    $tempstore = $this->tempStoreFactory->get('nys_senator_dashboard');

    // Initialize allowed senator IDs.
    $allowed_senator_ids = [];
    try {
      $current_user = $this->entityTypeManager
        ->getStorage('user')
        ->load($user_id);
      $field_senator_multiref = $current_user->field_senator_multiref?->getValue();
      if (is_array($field_senator_multiref)) {
        $allowed_senator_ids = array_column($field_senator_multiref, 'target_id');
      }
    }
    catch (\Throwable) {
      return FALSE;
    }

    // Update the active managed senator if allowed.
    if (in_array($senator_id, $allowed_senator_ids)) {
      try {
        $tempstore->set('active_managed_senator', $senator_id);
        if ($include_message) {
          Cache::invalidateTags(['tempstore_user:' . $user_id]);
          $this->messenger->addMessage($this->t('Your active managed senator has been updated.'));
        }
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
