<?php

namespace Drupal\nys_config\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
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
    $validators = [
      'file_validate_extensions' => ['pdf'],
    ];

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
    $form['session_state']['nys_senate_status'] = [
      '#title' => $this->t('Custom NY State Senate Status Text'),
      '#type'  => 'textfield',
      '#size' => 35,
      '#default_value' => $config->get('nys_senate_status'),
    ];
    $form['session_state']['nys_vid_redir_ip'] = [
      '#title' => $this->t('Video Redirect IP'),
      '#description' => $this->t('Enter an IP (or lower IP value if a range of addresses) in the format 123.456.789.012 that should be redirected to internal feeds when streaming video. Supports wildcards (123.456.*.*).'),
      '#type'  => 'textfield',
      '#size'  => 15,
      '#default_value' => $config->get('nys_vid_redir_ip'),
    ];
    $form['session_state']['nys_vid_redir_ip_2'] = [
      '#title' => $this->t('Video Redirect IP Range (Optional)'),
      '#description' => $this->t('If a range of addresses is being used, enter the top value of IP addresses. Supports wildcards (123.456.789.*).'),
      '#type'  => 'textfield',
      '#size'  => 15,
      '#default_value' => $config->get('nys_vid_redir_ip_2'),
    ];
    $form['session_state']['nys_vid_redir_url'] = [
      '#title' => $this->t('Video Redirect URL'),
      '#description' => $this->t('Enter the URL that IPs streaming internal video should be directed to.'),
      '#type'  => 'textfield',
      '#default_value' => $config->get('nys_vid_redir_url'),
    ];

    /*
     * PDF contents.
     */
    $form['pdfs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('PDFs'),
    ];
    $form['pdfs']['member_listing_pdf'] = [
      '#type' => 'managed_file',
      '#multiple' => FALSE,
      '#name' => 'member_listing_pdf',
      '#title' => $this->t('Member Listing PDF'),
      '#default_value' => $config->get('member_listing_pdf') ?? NULL,
      '#upload_location' => 'public://',
      '#upload_validators' => $validators,
    ];
    $form['pdfs']['session_calendar_pdf'] = [
      '#type' => 'managed_file',
      '#multiple' => FALSE,
      '#name' => 'session_calendar_pdf',
      '#title' => $this->t('Session Calendar PDF'),
      '#default_value' => $config->get('session_calendar_pdf') ?? NULL,
      '#upload_location' => 'public://',
      '#upload_validators' => $validators,
    ];
    $form['pdfs']['public_hearing_schedule'] = [
      '#type' => 'managed_file',
      '#multiple' => FALSE,
      '#name' => 'public_hearing_schedule',
      '#title' => $this->t('Public Hearing Schedule PDF'),
      '#default_value' => $config->get('public_hearing_schedule') ?? NULL,
      '#upload_location' => 'public://',
      '#upload_validators' => $validators,
    ];

    /*
     * Login Modal Form.
     */
    $form['login_modal'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login Modal Form'),
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
    $config->set('nys_senate_status', $form_state->getValue('nys_senate_status'));
    $config->set('nys_vid_redir_ip', $form_state->getValue('nys_vid_redir_ip'));
    $config->set('nys_vid_redir_ip_2', $form_state->getValue('nys_vid_redir_ip_2'));
    $config->set('nys_vid_redir_url', $form_state->getValue('nys_vid_redir_url'));

    if ($form_state->getValue('member_listing_pdf') != $config->get('member_listing_pdf')) {
      $file = $form_state->getValue('member_listing_pdf');
      if ($file) {
        $this->processUploadFile($file);
      }

      $config->set('member_listing_pdf', $form_state->getValue('member_listing_pdf'));
    }
    if ($form_state->getValue('session_calendar_pdf') != $config->get('session_calendar_pdf')) {
      $file = $form_state->getValue('session_calendar_pdf');
      if ($file) {
        $this->processUploadFile($file);
      }

      $config->set('session_calendar_pdf', $form_state->getValue('session_calendar_pdf'));
    }
    if ($form_state->getValue('public_hearing_schedule') != $config->get('public_hearing_schedule')) {
      $file = $form_state->getValue('public_hearing_schedule');
      if ($file) {
        $this->processUploadFile($file);
      }

      $config->set('public_hearing_schedule', $form_state->getValue('public_hearing_schedule'));
    }

    $config->set('user_login_header', $form_state->getValue('user_login_header'));
    $config->set('user_login_body', $form_state->getValue('user_login_body')['value']);
    $config->set('user_login_footer', $form_state->getValue('user_login_footer')['value']);
    $config->save();

    // Clear the cache.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['views:events']);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['views:homepage_hero']);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['node:homepage']);

    parent::submitForm($form, $form_state);
  }

  /**
   * Process the upload file.
   */
  public function processUploadFile($entity, $directory = 'public://pdfs/') {
    $file = File::load($entity[0]);
    if (!empty($file)) {
      $initial_path = \Drupal::service('file_system')->realpath($file->getFileUri());
      $file_destination = $directory . $file->getFilename();

      // Make sure that the directory is created.
      $file_system = \Drupal::service('file_system');
      $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      if (file_exists($initial_path)) {
        $file_repository = \Drupal::service('file.repository');
        $file_repository->move($file, $file_destination);

        $file->setFileUri($file_destination);
      }

      $file->setPermanent();
      $file->save();
    }
  }

}
