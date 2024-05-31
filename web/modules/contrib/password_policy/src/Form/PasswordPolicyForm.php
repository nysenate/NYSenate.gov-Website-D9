<?php

namespace Drupal\password_policy\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The general settings of the policy.
 */
abstract class PasswordPolicyForm extends EntityForm {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Plugin manager for constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   */
  public function __construct(PluginManagerInterface $manager, LanguageManagerInterface $language_manager, FormBuilderInterface $formBuilder) {
    $this->manager = $manager;
    $this->languageManager = $language_manager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.password_policy.password_constraint'),
      $container->get('language_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('PASSWORD POLICY'),
    ];

    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Policy Name'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('Enter label for this context.'),
    ];

    $form['general']['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => [$this, 'passwordPolicyExists'],
      ],
    ];

    $form['password_reset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password Reset Days'),
      '#description' => $this->t('User password will reset after the selected number of days. 0 days indicates that passwords never expire.'),
      '#default_value' => $this->entity->getPasswordReset(),
    ];
    $form['send_reset_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email upon password expiring'),
      '#description' => $this->t('If checked, an email will go to each user when their password expires, with a link to the request password reset email page.'),
      '#default_value' => $this->entity->getPasswordResetEmailValue(),
    ];

    $form['send_pending_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Send pending email days before'),
      '#description' => $this->t('Send password expiration pending email X days before expiration. 0 days indicates this email will not be sent. The box above must also be checked. Separate by comma if sending multiple notifications.'),
      '#default_value' => implode(',', $this->entity->getPasswordPendingValue()),
    ];

    $form['show_policy_table'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show policy table'),
      '#description' => $this->t('Indicate whether this policy should show the password policy table on the user add/edit form.'),
      '#default_value' => $this->entity->isPolicyTableShown(),
    ];

    return $form;
  }

  /**
   * Check to validate that the Password Policy name does not already exist.
   *
   * @param string $name
   *   The machine name of the context to validate.
   *
   * @return bool
   *   TRUE on context name already exist, FALSE on context name not exist.
   */
  public function passwordPolicyExists($name) {
    $entity = $this->entityTypeManager->getStorage('password_policy')->loadByProperties(['name' => $name]);

    return (bool) $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $reminderMails = explode(',', $form_state->getValue('send_pending_email'));
    // Sort mail reminders so we always check reminders from the "closest" to
    // the "largest".
    sort($reminderMails);
    $form_state->setValue('send_pending_email', $reminderMails);
    parent::submitForm($form, $form_state);
  }

}
