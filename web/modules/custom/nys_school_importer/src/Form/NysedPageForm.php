<?php

namespace Drupal\nys_school_importer\Form;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_school_importer\ImporterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form class for NYSED importer.
 */
class NysedPageForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Default object for nys_school_importer.importer service.
   *
   * @var \Drupal\nys_school_importer\ImporterHelper
   */
  protected $importerHelper;

  /**
   * Default object for state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Default object for extension.path.resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Default object for file_system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The constructor method.
   *
   * @param \Drupal\nys_school_importer\ImporterHelper $importer_helper
   *   The importer helper class.
   * @param \Drupal\Core\State\State $state
   *   The state class.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $path_resolver
   *   The extension path resolver class.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger class.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system class.
   */
  public function __construct(
        ImporterHelper $importer_helper,
        State $state,
        ExtensionPathResolver $path_resolver,
        Messenger $messenger,
        FileSystem $file_system
    ) {
    $this->importerHelper = $importer_helper;
    $this->state = $state;
    $this->pathResolver = $path_resolver;
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('nys_school_importer.importer'),
          $container->get('state'),
          $container->get('extension.path.resolver'),
          $container->get('messenger'),
          $container->get('file_system')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nysed_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Upload form.
    $form = [
      '#attributes' => [
        'enctype' => 'multipart/form-data',
      ],
      'html_markup' => [
        '#markup' => '<p>You can merge multiple NYSED data files by repeatedly uploading csv files. </p>
                      <p>You can get a NYSED csv data export at this link <a href="https://portal.nysed.gov/discoverer/app/export?event=startExport">https://portal.nysed.gov/discoverer/app/export?event=startExport</a></p>',
      ],
      'html_markup_2' => [
        '#markup' => '<h3>There are currently ' . $this->importerHelper->getNysedDataCount() . ' NYSED institutions.</h3>',
      ],
      'csvfile' => [
        '#title' => $this->t('CSV File'),
        '#type'  => 'file',
        '#description' => ($max_size = ini_get('upload_max_filesize')) ? $this->t('Due to server restrictions, the <strong>maximum upload file size is %max_size</strong>. Files that exceed this size will be disregarded.', ['%max_size' => format_size($max_size)]) : '',
      ],
      'submit' => [
        '#type' => 'submit',
        '#id' => 'submit_button',
        '#value' => $this->t('Commence Import'),
        '#submit' => [$this, 'importerFormSubmit'],
      ],
      'continue_button' => [
        '#type' => 'submit',
        '#id' => 'continue_button',
        '#value' => $this->t('Completed NYSED Uploads - Continue To School Import'),
        '#submit' => [
      [$this, 'continueToSchoolUpload'],
        ],
      ],
      '#validate' => [
      [$this, 'validateFileupload'],
      [$this, 'formValidate'],
      ],
    ];

    return $form;
  }

  /**
   * Custom validation for the file being uploaded.
   */
  public function validateFileupload(array &$form, FormStateInterface $form_state) {
    // If the continue_to_school_upload button was clicked valid.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#id'] == 'continue_button') {
      return;
    }

    // Looking for csv files.
    $validators = [
      'file_validate_extensions' => ['csv'],
    ];

    if ($file = file_save_upload('csvfile', $validators, "public://", $this->fileSystem::EXISTS_REPLACE)) {
      $form_state->setValue('csvupload', $file->destination);
    }
    else {
      $form_state->setError('csvfile', $this->t('Unable to copy upload file'));
    }
  }

  /**
   * Custom form validation.
   */
  public function formValidate(array &$form, FormStateInterface $form_state) {
    // If the file is specified.
    if (!empty($form_state->getValue('csvupload'))) {
      if ($handle = fopen($form_state->getValue('csvupload'), 'r')) {
        for ($i = 0; $i <= 10; $i++) {
          $line = fgetcsv($handle, 4096);
          if (empty($line) == FALSE && is_array($line) == TRUE) {
            $line_count = count($line);
            foreach ($line as $column) {
              if (($column == 'Institution Id' && $line_count == 19) || ($line_count == 19)) {
                $form_state->setError('csvfile', t('This file has the incorrect number of columns. Expecting 19'));
              }
            }
          }
        }
        fclose($handle);
      }
      else {
        $form_state->setError('csvfile', t('Unable to read uploaded file !filepath', ['!filepath' => $form_state['values']['csvupload']]));
      }
    }
  }

  /**
   * Custom submit handler.
   */
  public function importerFormSubmit(array &$form, FormStateInterface $form_state) {
    // Setup the batch.
    $batch = [
      'title' => $this->t('Importing School Names CSV ...'),
      'operations' => [],
      'init_message' => $this->t('Commencing'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => 'importFinished',
    ];

    if (!empty($form_state->getValue('csvupload'))) {
      // File uploaded.
      $this->state->set('nys_school_nysed_importer_csvupload', $form_state->getValue('csvupload'));

      // Clear the nys_school_names table.
      // nys_school_importer_clear_nys_school_names();
      // Reset the importer status.
      $this->state->set('nys_school_importer_failed', FALSE);

      // Load the nys_school_names table.
      if ($handle = fopen($form_state->getValue('csvupload'), 'r')) {
        $batch['operations'][] = [
          'rememberFilename',
          [$form_state->getValue('csvupload')],
        ];
        $line_count = 1;
        $first = TRUE;
        // Read the header line.
        $header_line = fgetcsv($handle, 4096);
        // Read the rest of the lines.
        $line = fgetcsv($handle, 4096);
        while ($line = fgetcsv($handle, 4096)) {
          /*
           * we use base64_encode to ensure we don't overload the batch
           * processor by stuffing complex objects into it
           */
          $line_count++;
          $batch['operations'][] = [
            'importSchoolNamesLine',
            [
              array_map('base64_encode', $line),
            ],
          ];
        }
        fclose($handle);
      } // we caught this in nys_school_nysed_importer_form_validate()
    } // we caught this in nys_school_nysed_importer_form_validate()
    batch_set($batch);
  }

  /**
   * Stores the filename.
   */
  public function rememberFilename($filename, &$context) {
    $context['results']['uploaded_filename'] = $filename;
  }

  /**
   * Processs the line.
   */
  public function importSchoolNamesLine($line, $session_nid, &$context = NULL) {
    $line = $cleaned_line = array_map('base64_decode', $line);
    if (is_numeric($line[0]) == TRUE && is_numeric($line[2]) == TRUE) {
      $this->importerHelper->insertOrUpdateNysedData(
            $line[0],
            $line[1],
            $line[2],
            $line[3],
            $line[4],
            $line[5],
            $line[6],
            $line[7],
            $line[8],
            $line[9],
            $line[10],
            $line[11],
            $line[12],
            $line[13],
            $line[14],
            $line[15],
            $line[16],
            $line[17],
            $line[18],
            $line[19]
        );
    }
  }

  /**
   * Handler for the continue_button.
   */
  public function continueToSchoolUpload($form, &$form_state) {
    $response = new RedirectResponse('nys-school-import');
    $response->send();
  }

  /**
   * Processsing complete.
   */
  public function importFinished($success, $results, $operations) {
    // If there were failures.
    if (!empty($results['failed_rows'])) {
      $dir = $this->fileSystem->realPath('/csvImporter/');
      $csv_filename = 'failed_rows-' . basename($results['uploaded_filename']);
      $csv_filepath = $dir . '/' . $csv_filename;
      $csv_url = Link::fromTextAndUrl(htmlspecialchars($csv_filename), $csv_filepath);
      $targs = [
        '!csv_url' => $csv_url->toString(),
        '%csv_filename' => $csv_filename,
        '%csv_filepath' => $csv_filepath,
      ];

      if ($this->fileSystem->prepareDirectory($dir, $this->fileSystem::CREATE_DIRECTORY)) {
        if ($handle = fopen($csv_filepath, 'w+')) {
          foreach ($results['failed_rows'] as $failed_row) {
            fputcsv($handle, $failed_row);
          }
          fclose($handle);
          $this->messenger->addError(t('Some rows failed to import. You may download a CSV of these rows: !csv_url', $targs));
        }
        else {
          $this->messenger->addError(t('Some rows failed to import, but unable to write error CSV to %csv_filepath', $targs));
        }
      }
      else {
        $this->messenger->addError(t('Some rows failed to import, but unable to create directory for error CSV at %csv_directory', $targs));
      }
    }

    $nys_school_nysed_importer_failed = $this->state->get('nys_school_importer_failed', FALSE);
    if ($nys_school_nysed_importer_failed == TRUE) {
      $this->messenger->addWarning($this->t('The School Survey failed because of a missing or mismatched County Taxonomy Term.'));
      $response = new RedirectResponse('nys-school-report');
      $response->send();
      return $this->t('The CSV import was not complete.');
    }
    else {
      $this->messenger->addStatus($this->t('The CSV import has completed.'));
      return $this->t('The CSV import has completed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
