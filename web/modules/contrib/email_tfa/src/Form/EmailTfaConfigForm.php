<?php

namespace Drupal\email_tfa\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TfaConfigForm settings.
 */
class EmailTfaConfigForm extends ConfigFormBase {
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_tfa_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['email_tfa.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo description for All Fields
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('email_tfa.settings');
    $form['email_tfa_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['email_tfa_settings']['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'email_tfa_settings',
      '#weight' => 0,
    ];

    $form['email_tfa_settings']['settings']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $config->get('status'),
      '#description' => $this->t('Enable Email TFA in your site'),
    ];

    $form['email_tfa_settings']['settings']['tracks'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pathway'),
      '#default_value' => $config->get('tracks'),
      '#options' => [
        'globally_enabled' => $this
          ->t('Globally Enabled.'),
        'optionally_by_users' => $this
          ->t('Users optionally can enable.'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="status"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="status"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t("Please choose your path on how the module can serve you."),
      '#required' => TRUE,
    ];

    $form['email_tfa_settings']['settings']['user_one'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude User One'),
      '#default_value' => $config->get('user_one'),
      '#description' => $this->t('Exclude user one (1) from this this process.'),
      '#states' => [
        'visible' => [
          ':input[name="tracks"]' => ['value' => 'globally_enabled'],
          ':input[name="status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email_tfa_settings']['settings']['role_exclusion_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Exclude roles'),
      '#default_value' => $config->get('role_exclusion_type') ?? 'disable_for',
      '#options' => [
        'disable_for' => $this->t('Disable Email TFA for users with any of the following roles.'),
        'force_for' => $this->t('Force Email TFA for users with any of the following roles.'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="tracks"]' => ['value' => 'globally_enabled'],
          ':input[name="status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Load all roles.
    $roles = user_role_names(TRUE);
    $form['email_tfa_settings']['settings']['ignore_role'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => $config->get('ignore_role'),
      '#options' => $roles,
      '#states' => [
        'visible' => [
          ':input[name="tracks"]' => ['value' => 'globally_enabled'],
          ':input[name="status"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="tracks"]' => ['value' => 'globally_enabled'],
        ],
      ],
    ];

    $form['email_tfa_settings']['global'] = [
      '#type' => 'details',
      '#title' => $this->t('global'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'email_tfa_settings',
      '#weight' => 0,
    ];

    $form['email_tfa_settings']['global']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('subject'),
      '#required' => TRUE,
    ];

    $form['email_tfa_settings']['global']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('body'),
      '#required' => TRUE,
    ];

    $form['email_tfa_settings']['global']['routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded Routes'),
      '#default_value' => $config->get('routes'),
      '#description' => $this->t("Please specify route names that will be excluded from being called at Email TFA form."),
    ];

    $form['email_tfa_settings']['global']['timeouts'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#default_value' => $config->get('timeouts'),
      '#required' => TRUE,
    ];

    $form['email_tfa_settings']['global']['security_code_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Security code length'),
      '#description' => $this->t('The number of digits the security code will have'),
      '#default_value' => $config->get('security_code_length'),
      '#options' => [
        '4' => $this->t('4'),
        '5' => $this->t('5'),
        '6' => $this->t('6'),
        '7' => $this->t('7'),
        '8' => $this->t('8'),
        '9' => $this->t('9'),
      ],
      '#required' => TRUE,
    ];

    $form['email_tfa_settings']['global']['verification_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Verification Form'),
    ];

    $form['email_tfa_settings']['global']['verification_form']['security_code_label_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security code field label'),
      '#description' => $this->t('The label of the security code field.'),
      '#default_value' => $config->get('security_code_label_text'),
    ];

    $form['email_tfa_settings']['global']['verification_form']['security_code_description_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Security code field description'),
      '#description' => $this->t('The description of the security code field (e.g. help text).'),
      '#default_value' => $config->get('security_code_description_text'),
    ];

    $form['email_tfa_settings']['global']['verification_form']['security_code_verify_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Verify button text'),
      '#description' => $this->t('The text to use on the verify button.'),
      '#default_value' => $config->get('security_code_verify_text'),
    ];

    $form['email_tfa_settings']['global']['verification_form']['security_code_interrupt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel button text'),
      '#description' => $this->t('The text to use on the cancel button.'),
      '#default_value' => $config->get('security_code_interrupt_text'),
    ];

    $form['email_tfa_settings']['global']['messaging'] = [
      '#type' => 'details',
      '#title' => $this->t('Messaging'),
    ];

    $form['email_tfa_settings']['global']['messaging']['verification_succeeded_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Success message'),
      '#description' => $this->t('Message used to inform the user of a successful verification.'),
      '#default_value' => $config->get('verification_succeeded_message'),
    ];

    $form['email_tfa_settings']['global']['messaging']['verification_failed_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Failed message'),
      '#description' => $this->t('Message used to inform the user of a failed verification.'),
      '#default_value' => $config->get('verification_failed_message'),
    ];

    $form['email_tfa_settings']['global']['messaging']['verification_interrupted_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Interrupted message'),
      '#description' => $this->t('Message used to inform the user of an interrupted verification.'),
      '#default_value' => $config->get('verification_interrupted_message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ignore_role = array_filter($form_state->getValue('ignore_role'));
    sort($ignore_role);
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable('email_tfa.settings');
    $config->set('status', $form_state->getValue('status'))->save();
    $config->set('tracks', $form_state->getValue('tracks'))->save();
    $config->set('user_one', $form_state->getValue('user_one'))->save();
    $config->set('ignore_role', $ignore_role)->save();
    $config->set('role_exclusion_type', $form_state->getValue('role_exclusion_type'))->save();
    $config->set('routes', $form_state->getValue('routes'))->save();
    $config->set('timeouts', $form_state->getValue('timeouts'))->save();
    $config->set('subject', $form_state->getValue('subject'))->save();
    $config->set('body', $form_state->getValue('body'))->save();
    $config->set('security_code_length', $form_state->getValue('security_code_length'))->save();
    $config->set('security_code_label_text', $form_state->getValue('security_code_label_text'))->save();
    $config->set('security_code_description_text', $form_state->getValue('security_code_description_text'))->save();
    $config->set('security_code_verify_text', $form_state->getValue('security_code_verify_text'))->save();
    $config->set('security_code_interrupt_text', $form_state->getValue('security_code_interrupt_text'))->save();
    $config->set('verification_succeeded_message', $form_state->getValue('verification_succeeded_message'))->save();
    $config->set('verification_failed_message', $form_state->getValue('verification_failed_message'))->save();
    $config->set('verification_interrupted_message', $form_state->getValue('verification_interrupted_message'))->save();

    $this->config('email_tfa.settings')->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('timeouts') < 60) {
      $form_state->setErrorByName('timeouts', $this->t('Must be higher than 60 seconds'));
    }

    $security_code = $form_state->getValue('security_code_length');
    if ($security_code < 4 || $security_code > 9) {
      $form_state->setErrorByName('security_code_length', $this->t('Security code must be 4 to 9 digits long.'));
    }
  }

}
