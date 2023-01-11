<?php

namespace Drupal\filefield_paths\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administration settings form for File (Field) Paths.
 *
 * @package Drupal\filefield_paths\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Fi;esystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $file_system) {
    parent::__construct($config_factory);
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filefield_paths_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'filefield_paths.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['temp_location'] = [
      '#title' => $this->t('Temporary file location'),
      '#type' => 'textfield',
      '#default_value' => $this->config('filefield_paths.settings')
        ->get('temp_location') ?: filefield_paths_recommended_temporary_scheme() . 'filefield_paths',
      '#description'   => t('The location that unprocessed files will be uploaded prior to being processed by File (Field) Paths.<br />It is recommended that you use the temporary file system (temporary://) or, as a 2nd choice, the private file system (private://) if your server configuration allows for one of those.<br /><strong>Never use the public directory (public://) if the site supports private files, or private files can be temporarily exposed publicly.</strong>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $scheme = $this->streamWrapperManager->getScheme($values['temp_location']);
    if (!$scheme) {
      $form_state->setErrorByName('temp_location', $this->t('Invalid file location. You must include a file stream wrapper (e.g., public://).'));

      return FALSE;
    }

    if (!$this->streamWrapperManager->isValidScheme($scheme)) {
      $form_state->setErrorByName('temp_location', $this->t('Invalid file stream wrapper.'));

      return FALSE;
    }

    if ((!is_dir($values['temp_location']) || !is_writable($values['temp_location'])) && !$this->fileSystem->prepareDirectory($values['temp_location'], FileSystem::CREATE_DIRECTORY | FileSystem::MODIFY_PERMISSIONS)) {
      $form_state->setErrorByName('temp_location', $this->t('File location can not be created or is not writable.'));

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('filefield_paths.settings')
      ->set('temp_location', $values['temp_location'])
      ->save();
  }

}
