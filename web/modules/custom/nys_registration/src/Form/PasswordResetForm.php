<?php

namespace Drupal\nys_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\user\ProfileForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to edit user's password.
 */
class PasswordResetForm extends ProfileForm {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, ModuleHandlerInterface $moduleHandler, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    $user = \Drupal::entityTypeManager()->getStorage('user')
      ->load(\Drupal::currentUser()->id());
    $this->setEntity($user);

    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
    $this->moduleHandler = $moduleHandler;
    $this->setOperation('default');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
          $container->get('entity.repository'),
          $container->get('language_manager'),
          $container->get('module_handler'),
          $container->get('entity_type.bundle.info'),
          $container->get('datetime.time')
      );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'nys_registration_password_reset_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Set password field to required.
    $form['account']['pass']['#required'] = TRUE;
    $form['account']['current_pass']['#type'] = 'hidden';

    // These account fields are not editable by the user.
    $form['account']['mail']['#access'] = FALSE;
    $form['account']['name']['#access'] = FALSE;
    $form['account']['status']['#access'] = FALSE;
    $form['account']['roles']['#access'] = FALSE;
    $form['account']['notify']['#access'] = FALSE;

    if (isset($form['actions']['delete'])) {
      $form['actions']['delete']['#access'] = FALSE;
    }

    // These fields are not editable by the user.
    $disable = [
      'language',
      'timezone',
      'email_tfa_status',
      'field_address',
      'field_agree_to_terms',
      'field_dateofbirth',
      'field_district',
      'field_first_name',
      'field_gender_user',
      'field_last_name',
      'field_last_password_reset',
      'field_ldap_username',
      'field_password_expiration',
      'field_profile_picture',
      'field_senator_inbox_access',
      'field_senator_multiref',
      'field_top_issue',
      'field_user_banned_comments',
      'field_user_phone_no',
      'field_user_receive_emails',
      'field_voting_auto_subscribe',
      'path',
    ];
    foreach ($disable as $name) {
      $form[$name]['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromUserInput('/dashboard/edit'));

    parent::submitForm($form, $form_state);
  }

}
