<?php

namespace Drupal\nys_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class for nys config.
 */
class ConfigForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static($container->get('config.factory'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_config.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('nys_config.settings');

    /*
     * GOVERNOR.
     */
    $form['governor'] = [
      '#type' => 'fieldset',
      '#title' => t('Current Governor'),
      '#description' => $this->t("Variations for displaying the current governor's name."),
    ];

    $form['governor']['governor_full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#default_value' => $config->get('governor_full_name'),
      '#description' => $this->t("Governor's full name. (e.g. \"Franklin D. Roosevelt\" or \"Nelson Rockefeller\")"),
    ];

    $form['governor']['governor_last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $config->get('governor_last_name'),
      '#description' => $this->t("Governor's last name. (e.g. \"Roosevelt\" or \"Rockefeller\")"),
    ];

    /*
     * Previous Session.
     */
    $form['previous_session'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Previous Session Query Cache config'),
    ];
    $form['previous_session']['nys_access_permissions_prev_query_ttl'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache Lifetime'),
      '#options' => [
        '+1 hour' => $this->t('1 hour'),
        '+2 hours' => $this->t('2 hours'),
        '+4 hours' => $this->t('4 hours'),
        '+6 hours' => $this->t('6 hours'),
        '+12 hours' => $this->t('12 hours'),
        '+24 hours' => $this->t('24 hours'),
        '+3 days' => $this->t('3 days'),
        '+1 week' => $this->t('1 week'),
      ],
      '#empty_option' => $this->t('-- Select Lifetime --'),
      '#description' => $this->t('Select the Previous Session Query Cache lifetime. This determines how long the cache for queries for Previous Session is valid for.'),
      '#default_value' => $config->get('nys_access_permissions_prev_query_ttl'),
    ];

    /*
     * Session Status.
     */
    $form['session_state'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Session Status'),
      '#description' => $this->t('Data points related to state of the Session and streaming.'),
    ];

    $form['session_state']['nys_session_status'] = [
      '#title' => $this->t('Current NY State Senate Status'),
      '#type'  => 'select',
      '#default_value' => $config->get('nys_session_status'),
      '#options' => [
        'in_session' => $this->t('In Session'),
        'out_session' => $this->t('Out of Session'),
        'budget_week' => $this->t('Budget Week'),
      ],
    ];
    $form['session_state']['nys_session_year'] = [
      '#title' => $this->t('Current NY State Senate Session Year'),
      '#type'  => 'textfield',
      '#size' => 4,
      '#default_value' => $config->get('nys_session_year'),
    ];

    /*
     * Login Modal Form.
     */
    $form['login_modal'] = [
      '#type' => 'fieldset',
      '#title' => t('Login Modal Form'),
      '#description' => $this->t("Variations for displaying the Login Modal Form content."),
    ];
    $form['login_modal']['user_login_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#default_value' => $config->get('user_login_header'),
    ];
    $form['login_modal']['user_login_body'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#allowed_formats' => ['full_html'],
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('user_login_body'),
    ];
    $form['login_modal']['user_login_footer'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#allowed_formats' => ['full_html'],
      '#title' => $this->t('Footer'),
      '#default_value' => $config->get('user_login_footer'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nys_config.settings');
    $config->set('governor_full_name', $form_state->getValue('governor_full_name'));
    $config->set('governor_last_name', $form_state->getValue('governor_last_name'));
    $config->set('nys_access_permissions_prev_query_ttl', $form_state->getValue('nys_access_permissions_prev_query_ttl'));
    $config->set('nys_session_status', $form_state->getValue('nys_session_status'));
    $config->set('nys_session_year', $form_state->getValue('nys_session_year'));
    $config->set('user_login_header', $form_state->getValue('user_login_header'));
    $config->set('user_login_body', $form_state->getValue('user_login_body')['value']);
    $config->set('user_login_footer', $form_state->getValue('user_login_footer')['value']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
