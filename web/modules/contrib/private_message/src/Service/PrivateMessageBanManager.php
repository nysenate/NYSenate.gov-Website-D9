<?php

namespace Drupal\private_message\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\private_message\Entity\PrivateMessageBanInterface;
use Drupal\user\UserStorageInterface;

/**
 * The Private Message Ban manager service.
 */
class PrivateMessageBanManager implements PrivateMessageBanManagerInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The user entity manager.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * The Private Message Ban storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $banStorage;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private MessengerInterface $messenger;

  /**
   * Constructs a PrivateMessageBanManager object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    MessengerInterface $messenger
  ) {
    $this->currentUser = $currentUser;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->banStorage = $entityTypeManager->getStorage('private_message_ban');
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function isBanned(int $user_id): bool {
    $select = $this
      ->database
      ->select('private_message_ban', 'pmb');
    $select
      ->addExpression('1');

    return (bool) $select->condition('pmb.owner', $this->currentUser->id())
      ->condition('pmb.target', $user_id)
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentUserBannedByUser(int $user_id): bool {
    $select = $this
      ->database
      ->select('private_message_ban', 'pmb');
    $select
      ->addExpression('1');

    return (bool) $select->condition('pmb.owner', $user_id)
      ->condition('pmb.target', $this->currentUser->id())
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getBannedUsers(int $user_id): array {
    return $this
      ->database
      ->select('private_message_ban', 'pmb')
      ->fields('pmb', ['target'])
      ->condition('pmb.owner', $user_id)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function unbanUser(int $user_id) {
    $ban = $this->findBanEntity($this->currentUser->id(), $user_id);
    if (!$ban) {
      // The user is not banned; just return.
      return;
    }

    // Delete the ban and display a message.
    $ban->delete();

    $this->messenger
      ->addStatus($this->t('The user %name has been unbanned.', ['%name' => $this->getUserDisplayName($user_id)]));
  }

  /**
   * {@inheritdoc}
   */
  public function banUser(int $user_id) {
    $ban = $this->findBanEntity($this->currentUser->id(), $user_id);
    if ($ban) {
      // The user is already banned; just return.
      return;
    }

    // Create the ban and display a message.
    $this
      ->banStorage
      ->create([
        'owner' => $this->currentUser->id(),
        'target' => $user_id,
      ])
      ->save();

    $this->messenger
      ->addStatus($this->t('The user %name has been banned.', ['%name' => $this->getUserDisplayName($user_id)]));
  }

  /**
   * A helper method to retrieve the user account name.
   *
   * @param int $user_id
   *   The user id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The user display name or translatable 'Unknown' markup.
   */
  protected function getUserDisplayName(int $user_id) {
    /** @var \Drupal\user\UserInterface|null $user */
    $user = $this->userStorage->load($user_id);
    return $user ? $user->getAccountName() : $this->t('Unknown');
  }

  /**
   * Finds the ban entity for the given owner and target.
   *
   * @param int $owner
   *   The ban entity owner.
   * @param int $user_id
   *   The ID of user being banned.
   *
   * @return \Drupal\private_message\Entity\PrivateMessageBanInterface|null
   *   The ban entity or NULL if not found.
   */
  protected function findBanEntity(int $owner, int $user_id): ?PrivateMessageBanInterface {
    // Find the ban entity.
    $bans = $this
      ->banStorage
      ->loadByProperties([
        'owner' => $owner,
        'target' => $user_id,
      ]);

    // reset() returns the first element of the array or FALSE if the array is
    // empty, but we want the return value to be NULL if the array is empty.
    return reset($bans) ?: NULL;
  }

}
