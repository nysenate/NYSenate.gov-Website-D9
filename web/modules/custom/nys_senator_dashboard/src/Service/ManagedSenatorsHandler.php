<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\nys_senators\SenatorsHelper;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $tempStore;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
    $this->tempStore = $tempStoreFactory->get('nys_senator_dashboard');
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
    $managed_senators = [];

    try {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    }
    catch (\Throwable) {
      return $managed_senators;
    }

    if (in_array('microsite_content_producer', $user->getRoles())) {
      if ($user->hasField('field_senator_multiref')) {
        $mcp_managed_senators = $tids_only
          ? array_column($user->field_senator_multiref->getValue(), 'target_id')
          : $user->field_senator_multiref->referencedEntities();
        $managed_senators = array_merge($managed_senators, $mcp_managed_senators);
      }
    }

    if (in_array('legislative_correspondent', $user->getRoles())) {
      if ($user->hasField('field_senator_inbox_access')) {
        $lc_managed_senators = $tids_only
          ? array_column($user->field_senator_inbox_access->getValue(), 'target_id')
          : $user->field_senator_inbox_access->referencedEntities();
        $managed_senators = array_merge($managed_senators, $lc_managed_senators);
      }
    }

    return $managed_senators;
  }

  /**
   * Gets the current user's active managed senator.
   *
   * @param bool $tid_only
   *   Whether to return TIDs or entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface|int
   *   The senator entity or TID.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the active senator cannot be established.
   */
  public function getActiveSenator(bool $tid_only = TRUE): EntityInterface|int {
    // Get the active senator from the temp store.
    $stored_active_senator_tid = $this->tempStore->get('active_managed_senator_tid');
    if (
      $stored_active_senator_tid !== NULL
      && in_array($stored_active_senator_tid, $this->getManagedSenators())
    ) {
      $active_senator_tid = $stored_active_senator_tid;
    }

    // If not available, verify and set default active senator.
    if (empty($active_senator_tid)) {
      return $this->verifyAndSetDefaultActiveSenator($tid_only);
    }

    if ($tid_only) {
      return $active_senator_tid;
    }
    else {
      try {
        $active_senator = $this->entityTypeManager->getStorage('taxonomy_term')
          ->load($active_senator_tid);
      }
      catch (\Exception) {
        return $this->verifyAndSetDefaultActiveSenator(FALSE);
      }
      return $active_senator;
    }
  }

  /**
   * Verifies and sets a default active senator.
   *
   * Throws access denied exception in all failure scenarios, so that Drupal can
   * handle as a normal access denied scenario (i.e. redirect to login page).
   *
   * @param bool $tid_only
   *   Whether to return TIDs or entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface|int
   *   The senator entity or TID.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the active senator cannot be established.
   */
  private function verifyAndSetDefaultActiveSenator(bool $tid_only = TRUE): EntityInterface|int {
    $error_message = 'Error establishing active senator required for this request.';

    $managed_senators = $this->getManagedSenators(FALSE);
    if (count($managed_senators) > 0) {
      try {
        $this->tempStore->set('active_managed_senator_tid', $managed_senators[0]->id());
      }
      catch (TempStoreException) {
        throw new AccessDeniedHttpException($error_message);
      }
      Cache::invalidateTags([
        'tempstore_user:' . $this->currentUser->id(),
        'user:' . $this->currentUser->id(),
      ]);
      $active_senator = $managed_senators[0];
      $active_senator_tid = $active_senator->id();

      if ($tid_only) {
        return $active_senator_tid;
      }
      else {
        return $active_senator;
      }
    }
    else {
      throw new AccessDeniedHttpException($error_message);
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
    $error_message = $this->t('There was an error updating your active managed senator.');
    if (in_array($senator_id, $allowed_senator_ids)) {
      try {
        $this->tempStore->set('active_managed_senator_tid', $senator_id);
        if ($include_message) {
          $this->messenger->addMessage($this->t('Your active managed senator has been updated.'));
        }
        Cache::invalidateTags([
          'tempstore_user:' . $this->currentUser->id(),
          'user:' . $this->currentUser->id(),
        ]);
        return TRUE;
      }
      catch (\Exception) {
        if ($include_message) {
          $this->messenger->addError($error_message);
        }
        return FALSE;
      }
    }
    else {
      if ($include_message) {
        $this->messenger->addError($error_message);
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
    /** @var \Drupal\taxonomy\Entity\Term $senator */
    $senator = $this->getActiveSenator(FALSE);
    return $this->senatorsHelper->getMicrositeUrl($senator);
  }

  /**
   * Gets the current user's active senator's district ID.
   *
   * @return int
   *   The district TID, or 0 if not found.
   */
  public function getActiveSenatorDistrictId(): int {
    /** @var \Drupal\taxonomy\Entity\Term $senator */
    $senator = $this->getActiveSenator(FALSE);
    return $this->senatorsHelper->loadDistrict($senator)?->id() ?? 0;
  }

}
