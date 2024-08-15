<?php

namespace Drupal\nys_school_importer\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_school_importer\ImporterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form class for school survey.
 */
class SurveyPageForm extends FormBase {

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
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger class.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system class.
   */
  public function __construct(
        ImporterHelper $importer_helper,
        State $state,
        Messenger $messenger,
        FileSystem $file_system
    ) {
    $this->importerHelper = $importer_helper;
    $this->state = $state;
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
          $container->get('messenger'),
          $container->get('file_system')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_school_importer_form';
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
      'csvfile' => [
        '#title' => $this->t('CSV File'),
        '#type'  => 'file',
        '#description' => ($max_size = ini_get('upload_max_filesize')) ? $this->t('Due to server restrictions, the <strong>maximum upload file size is %max_size</strong>. Files that exceed this size will be disregarded.', ['%max_size' => ByteSizeMarkup::create($max_size)]) : '',
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Commence Import'),
      ],
      '#validate' => [
      [$this, 'validateFileUpload'],
      [$this, 'formValidate'],
      ],
    ];

    return $form;
  }

  /**
   * Validates the file upload.
   */
  public function validateFileupload(array &$form, FormStateInterface $form_state) {
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
   * Validate the inout file.
   */
  public function formValidate(array &$form, FormStateInterface $form_state) {
    // If the file is specified.
    if (!empty($form_state->getValue('csvupload'))) {
      if ($handle = fopen($form_state->getValue('csvupload'), 'r')) {
        $line_count = 1;
        if ($line = fgetcsv($handle, 4096)) {

          // Begin Validation.
          if (count($line) != 23) {
            $form_state->setError('csvfile', $this->t('This file has the incorrect number of columns. Expecting 23'));
          }

          if ($this->importerHelper->validateFile($line) == FALSE) {
            $form_state->setError('csvfile', $this->t('The Columns in the Import File csv do not match the schema.'));
          }

          // End validating aspects of the CSV file.
        }
        fclose($handle);
      }
      else {
        $form_state->setError('csvfile', $this->t('Unable to read uploaded file %filepath', ['%filepath' => $form_state->getValue('csvupload')]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Setup the batch.
    $batch = [
      'title' => $this->t('Importing School Names CSV ...'),
      'operations' => [],
      'init_message' => $this->t('Commencing'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => 'importFinished',
    ];

    if (isset($form_state['values']['csvupload'])) {
      // File uploaded.
      $this->state->set('nys_school_importer_csvupload', $form_state->getValue('csvupload'));

      // Clear the nys_school_names table.
      $this->importerHelper->clearNysSchoolNames();

      // Reset the importer status.
      $this->state->set('nys_school_importer_failed', FALSE);

      // Load the nys_school_names table.
      if ($handle = fopen($form_state['values']['csvupload'], 'r')) {
        $batch['operations'][] = [
          'rememberFilename',
          [$form_state['values']['csvupload']],
        ];
        $line_count = 1;
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
      } // we caught this in nys_school_importer_form_validate()
    } // we caught this in nys_school_importer_form_validate()
    batch_set($batch);
  }

  /**
   * Helper function.
   */
  public function rememberFilename($filename, &$context) {
    $context['results']['uploaded_filename'] = $filename;
  }

  /**
   * Processsing complete.
   */
  public function importSchoolNamesLine($line, $session_nid, &$context = NULL) {
    // Handle encoded file.
    $line = array_map('base64_decode', $line);

    // Get the Key columns and save them.
    $legal_name = $line[$this->importerHelper->getColumnNumber('LEGAL NAME') - 1];
    $grade_organization = $line[$this->importerHelper->getColumnNumber('GRADE ORGANIZATION DESCRIPTION') - 1];
    $city = $line[$this->importerHelper->getColumnNumber('CITY') - 1];
    $zip = $line[$this->importerHelper->getColumnNumber('ZIP') - 1];
    $this->importerHelper->createSchoolName($legal_name, $grade_organization, $city, $zip);

    // Get the county name for the referential integrity check.
    $county_name = $line[$this->importerHelper->getColumnNumber('COUNTY') - 1];
    $county_tid = $this->importerHelper->getCountyTid($county_name);
    if ($county_tid == FALSE) {
      // The supplied county name is not in the County taxonomy.
      $this->messenger->addStatus(
            $this->t(
                "County `%country` not found in taxonomy for - %legal_name, %grade_organization, %city, %zip", [
                  '%country_name' => $county_name,
                  '%legal_name' => $legal_name,
                  '%grade_organization' => $grade_organization,
                  '%city' => $city,
                  '%zip' => $zip,
                ]
            )
        );
      $this->state->set('nys_school_importer_failed', TRUE);
    }

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
          $this->messenger->addError($this->t('Some rows failed to import. You may download a CSV of these rows: !csv_url', $targs));
        }
        else {
          $this->messenger->addError($this->t('Some rows failed to import, but unable to write error CSV to %csv_filepath', $targs));
        }
      }
      else {
        $this->messenger->addError($this->t('Some rows failed to import, but unable to create directory for error CSV at %csv_directory', $targs));
      }
    }

    $nys_school_importer_failed = $this->state->get('nys_school_importer_failed', FALSE);
    if ($nys_school_importer_failed == TRUE) {
      $this->messenger->addWarning($this->t('The School Survey failed because of a missing or mismatched County Taxonomy Term.'));
      $response = new RedirectResponse('nys-school-report');
      $response->send();
      return $this->t('The CSV import was not complete.');
    }
    else {
      $this->messenger->addStatus(t('The CSV import has completed.'));
      $response = new RedirectResponse('nys-school-analyze');
      $response->send();
      return $this->t('The CSV import has completed.');
    }
  }

}
