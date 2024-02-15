<?php

namespace Drupal\private_message\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\private_message\Service\PrivateMessageBanManagerInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User unban confirmation form.
 */
class ConfirmUnbanUserForm extends ConfirmFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The user manager service.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userManager;

  /**
   * The Private Message Ban manager.
   *
   * @var \Drupal\private_message\Service\PrivateMessageBanManagerInterface
   */
  private PrivateMessageBanManagerInterface $privateMessageBanManager;

  /**
   * The user to unban.
   *
   * @var \Drupal\user\UserInterface|null
   */
  private ?UserInterface $user;

  /**
   * Constructs a BlockUserForm object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\private_message\Service\PrivateMessageBanManagerInterface $privateMessageBanManager
   *   The Private Message Ban manager.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    PrivateMessageBanManagerInterface $privateMessageBanManager
  ) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->userManager = $entityTypeManager->getStorage('user');
    $this->privateMessageBanManager = $privateMessageBanManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('private_message.ban_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'private_message_confirm_unblock_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL): array {
    $this->user = $this->userManager->load($user);

    if ($this->user === NULL) {
      return [];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Add a security if the user id is unknown.
    if (empty($this->user)) {
      $form_state->setError($form, $this->t('The user id is unknown.'));
    }

    // Add security to prevent unblocking ourselves.
    if ($this->user->id() === $this->currentUser->id()) {
      $form_state->setError($form, $this->t("You can't unblock yourself."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_state->setRedirect('private_message.private_message_page');

    // If user not banned, do nothing.
    if (!$this->privateMessageBanManager->isBanned($this->user->id())) {
      return;
    }

    $this->privateMessageBanManager->unbanUser($this->user->id());

  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    return $this->t('Are you sure you want to unblock user <em>%user</em>?', ['%user' => $this->user->getAccountName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('By confirming, you will be able to send messages to this user.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('private_message.ban_page');
  }

}
