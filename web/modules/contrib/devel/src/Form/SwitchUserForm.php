<?php

namespace Drupal\devel\Form;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define a form to allow the user to switch and become another user.
 */
class SwitchUserForm extends FormBase {

  /**
   * The csrf token generator.
   */
  protected CsrfTokenGenerator $csrfToken;

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * Constructs a new SwitchUserForm object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    CsrfTokenGenerator $csrf_token_generator,
    UserStorageInterface $user_storage,
    TranslationInterface $string_translation
  ) {
    $this->csrfToken = $csrf_token_generator;
    $this->userStorage = $user_storage;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('csrf_token'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_switchuser_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['autocomplete'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $form['autocomplete']['userid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Username'),
      '#placeholder' => $this->t('Enter username'),
      '#target_type' => 'user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#process_default_value' => FALSE,
      '#title_display' => 'invisible',
      '#required' => TRUE,
      '#size' => '28',
    ];

    $form['autocomplete']['actions'] = ['#type' => 'actions'];
    $form['autocomplete']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $userId = $form_state->getValue('userid');
    if ($userId === NULL) {
      $form_state->setErrorByName('userid', $this->t('Username not found'));
      return;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->userStorage->load($userId);
    if (!$account) {
      $form_state->setErrorByName('userid', $this->t('Username not found'));
    }
    else {
      $form_state->setValue('username', $account->getAccountName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // We cannot rely on automatic token creation, since the csrf seed changes
    // after the redirect and the generated token is not more valid.
    // @todo find another way to do this.
    $url = Url::fromRoute('devel.switch', ['name' => $form_state->getValue('username')]);
    $url->setOption('query', ['token' => $this->csrfToken->get($url->getInternalPath())]);

    $form_state->setRedirectUrl($url);
  }

}
