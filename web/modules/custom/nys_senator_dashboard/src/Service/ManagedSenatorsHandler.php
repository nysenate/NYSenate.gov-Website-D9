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
use Drupal\nys_users\UsersHelper;
use Drupal\taxonomy\Entity\Term;
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
   * Loads all terms for senators managed by the current user.
   *
   * @return array
   *   An array of senator Term entities managed by the user, or an empty
   *   array and a messenger alert on error.
   */
  public function getManagedSenators(): array {
    try {
      $senators = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadMultiple(UsersHelper::getAllManagedSenators());
    }
    catch (\Throwable) {
      $senators = [];
      $this->messenger->addError($this->t('Failed to load managed senators.'));
    }

    return $senators;
  }

  /**
   * Sets the active managed senator for the current user.
   *
   * @param \Drupal\taxonomy\Entity\Term|int $id
   *   A loaded senator Term, or the tid of a senator Term.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the temp store has any error.
   */
  public function setActiveSenator(Term|int $id): void {
    if ($id instanceof Term) {
      $id = $id->id();
    }
    try {
      $this->tempStore->set('active_managed_senator_tid', $id);
    }
    catch (TempStoreException) {
      throw new AccessDeniedHttpException("Could not set active managed senator.");
    }
    Cache::invalidateTags([
      'tempstore_user:' . $this->currentUser->id(),
      'user:' . $this->currentUser->id(),
    ]);
  }

  /**
   * Gets the current user's active managed senator.
   *
   * @param bool $tid_only
   *   Whether to return TIDs or entities.
   *
   * @return \Drupal\taxonomy\Entity\Term|int|null
   *   The senator entity or TID.
   */
  public function getActiveSenator(bool $tid_only = TRUE): Term|int|null {
    // Get the active senator from the temp store.
    $stored_tid = $this->tempStore->get('active_managed_senator_tid');

    // If the user does not have access to the stored senator (or if there is
    // no active set), try to set a default active.  Note that will silently
    // replace the active senator if permissions change mid-session.
    if (!in_array($stored_tid, UsersHelper::getAllManagedSenators())) {
      $senator = $this->setDefaultActiveSenator(FALSE);
      $stored_tid = $senator->id();
    }
    else {
      try {
        $senator = $this->entityTypeManager->getStorage('taxonomy_term')
          ->load($stored_tid);
      }
      catch (\Throwable) {
        $senator = NULL;
        $stored_tid = 0;
      }
    }

    return $tid_only ? $stored_tid : $senator;
  }

  /**
   * Sets the default active senator for the current user.
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
  protected function setDefaultActiveSenator(bool $tid_only = TRUE): EntityInterface|int {
    $senator = current($this->getManagedSenators());
    if (!$senator) {
      throw new AccessDeniedHttpException("Could not establish default active senator.");
    }
    $this->setActiveSenator($senator);
    return $tid_only ? $senator->id() : $senator;
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
    $ret = FALSE;
    $allowed_senator_ids = UsersHelper::getAllManagedSenators();

    // If the user is allowed to manage this senator, try to set the store.
    if (in_array($senator_id, $allowed_senator_ids)) {
      try {
        $this->setActiveSenator($senator_id);
        $msg = $this->t('Your active managed senator has been updated.');
        $ret = TRUE;
      }
      catch (\Throwable) {
        $msg = $this->t("Failed to set active managed senator.");
      }
    }
    // Canary for "access denied".
    else {
      $msg = $this->t('Could not update active managed senator.');
    }

    // If a message was desired, send it.
    if ($include_message) {
      $func = $ret ? 'addMessage' : 'addError';
      $this->messenger->$func($msg);
    }

    return $ret;
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
