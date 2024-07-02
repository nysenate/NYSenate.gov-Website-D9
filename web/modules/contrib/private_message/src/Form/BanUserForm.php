<?php

namespace Drupal\private_message\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Service\PrivateMessageBanManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Private Message users banning form.
 */
class BanUserForm extends FormBase {

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
   * The private message configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Private Message Ban manager.
   *
   * @var \Drupal\private_message\Service\PrivateMessageBanManagerInterface
   */
  private PrivateMessageBanManagerInterface $privateMessageBanManager;

  /**
   * Constructs a BanUserForm object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\private_message\Service\PrivateMessageBanManagerInterface $privateMessageBanManager
   *   The Private Message Ban manager.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    PrivateMessageBanManagerInterface $privateMessageBanManager
  ) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->privateMessageBanManager = $privateMessageBanManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('private_message.ban_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'block_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $target_id = NULL): array {

    $config = $this->configFactory->get('private_message.settings');

    if ($target_id === NULL) {
      $form['user_name'] = [
        '#title' => ('Select User'),
        '#type' => 'textfield',
        '#required' => FALSE,
        '#attributes' => [
          'class' => [
            'private-message-ban-autocomplete',
          ],
        ],
        '#autocomplete_route_name' => 'private_message.ban_autocomplete',
        '#attached' => [
          'library' => [
            'private_message/ban_autocomplete',
          ],
        ],
      ];
    }

    $form['banned_user'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Blocked User'),
      '#default_value' => $target_id,
      '#required' => FALSE,
    ];

    $submitLabel = $config->get('ban_label');
    if ($target_id && $this->privateMessageBanManager->isBanned($target_id)) {
      $submitLabel = $config->get('unban_label');
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $submitLabel,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $user_id_field = !empty($form_state->getValue('banned_user')) ? 'banned_user' : 'user_name';
    $user_id = $form_state->getValue('banned_user');

    // Add security to prevent blocking ourselves.
    if ($user_id === $this->currentUser->id()) {
      $form_state->setErrorByName($user_id_field, $this->t("You can't block yourself."));
    }

    // Add a security if the user id is unknown.
    if (empty($user_id) ||
      empty($this->entityTypeManager->getStorage('user')->load($user_id))) {
      $form_state->setErrorByName($user_id_field, $this->t('The user id is unknown.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $target_id = $form_state->getValue('banned_user');

    // Unban if already banned.
    if ($this->privateMessageBanManager->isBanned($target_id)) {
      $this->privateMessageBanManager->unbanUser($target_id);
    }
    // Ban if not banned.
    else {
      $this->privateMessageBanManager->banUser($target_id);
    }
  }

}
