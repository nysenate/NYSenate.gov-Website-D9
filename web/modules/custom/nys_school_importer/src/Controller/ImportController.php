<?php

namespace Drupal\nys_school_importer\Controller;

use Drupal\Core\State\State;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\nys_school_importer\ImporterHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The controller class for importer functionality.
 */
class ImportController extends ControllerBase {

  /**
   * Default object for state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Default object for nys_school_importer.importer service.
   *
   * @var \Drupal\nys_school_importer\ImporterHelper
   */
  protected $importerHelper;

  /**
   * Default object for form_builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilderInterface;

  /**
   * Default object for renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $rendererInterface;

  /**
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Default object for database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The constructor method.
   *
   * @param \Drupal\nys_school_importer\ImporterHelper $importer_helper
   *   The importer helper service.
   * @param \Drupal\Core\State\State $state
   *   The state service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder_interface
   *   The form builder interface service.
   * @param \Drupal\Core\Render\RendererInterface $renderer_interface
   *   The renderer interface service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection class.
   */
  public function __construct(
    ImporterHelper $importer_helper,
    State $state,
    FormBuilderInterface $form_builder_interface,
    RendererInterface $renderer_interface,
    Messenger $messenger,
    Connection $connection
  ) {
    $this->importerHelper = $importer_helper;
    $this->state = $state;
    $this->formBuilderInterface = $form_builder_interface;
    $this->rendererInterface = $renderer_interface;
    $this->messenger = $messenger;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container_interface) {
    return new static(
      $container_interface->get('nys_school_importer.importer'),
      $container_interface->get('state'),
      $container_interface->get('form_builder'),
      $container_interface->get('renderer'),
      $container_interface->get('messenger'),
      $container_interface->get('database'),
    );
  }

  /**
   * Page handler for the nys-school-import page.
   */
  public function importPage() {
    // See if there is an upload file.
    $input_file = $this->state->get('nys_school_importer_csvupload', '');
    if (empty($input_file) == FALSE) {
      // If no name needs more than 4 keys proceed.
      if ($this->importerHelper->getNumMaxNamesIndex() < 5) {
        // Process the import file.
        $this->processImport();
      }
      else {
        // Pose a form to continue with the import.
        $form = $this->formBuilderInterface->getForm('Drupal\nys_school_importer\Form\ContinueForm');
        $output = "<p>Some School Names are the same and adding grade_organization city and zip code was not enough to make them unique.</p>
                  <p>You can go back and fix the issue in the CSV file or Continue with the import and fix the issues later.</p>
                  <p>If you continue a unique name will be formed by adding a number to the school name, grade_organization city and zip code.";
        $output .= $this->rendererInterface->render($form);
        return $output;
      }
    }
    else {
      // File Upload Not Specified.
      $this->messenger->addStatus($this->t('No Upload File Was Specifid. Try Again.'));
      $response = new RedirectResponse('admin/config/system/nys-school-import');
      $response->send();
    }

    return "<h1>Import Schools</h1>";
  }

  /**
   * Process and import a batch of schools.
   */
  public function processImport() {
    $progress = 0;
    $limit = 1;
    $batch = [
      'operations' => [],
      'finished' => 'processBatchFinished',
      'title' => $this->t('Importing Schools'),
      'init_message' => $this->t('Import is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Import has encountered an error.'),
    ];

    $file_path = $this->state->get('nys_school_importer_csvupload', '');
    if (file_exists($file_path) == TRUE) {
      // Load the file and process it.
      $row = 1;
      if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
          if ($row == 1) {
            // This is the first `column title` row.
            $valid_columns = $this->importerHelper->validateFile($data);
            if ($valid_columns == FALSE) {
              print "The Columns in the Import File csv do not match the schema. \r\n";
              fclose($handle);
              return;
            }
          }
          else {
            // Ths is a regular data row.
            $batch['operations'][] = [
              'importLine',
              [
                $progress,
                $limit,
                $data,
              ],
            ];

          }
          $row++;
        }
        fclose($handle);
      }
    }

    batch_set($batch);
    batch_process('/admin/nys-school-report');
  }

  /**
   * This is what runs multiple times per batch.
   */
  public function importLine($progress, $limit, $line, &$context) {
    // Get the Key columns.
    $raw_legal_name = $line[$this->importerHelper->getColumnNumber('LEGAL NAME') - 1];
    // Since the masaged name is what is in the node.
    // The lookup needs to be for a massaged name.
    // Because (THE) is at the end of the name in the $raw_legal_name sometimes.
    $legal_name = $this->importerHelper->cleanupName($raw_legal_name);
    $grade_organization = $line[$this->importerHelper->getColumnNumber('GRADE ORGANIZATION DESCRIPTION') - 1];
    $city = $line[$this->importerHelper->getColumnNumber('CITY') - 1];
    $zip = $line[$this->importerHelper->getColumnNumber('ZIP') - 1];

    // See if the School exists already.
    $node = $this->importerHelper->loadSchoolNode($legal_name, $grade_organization, $city, $zip);

    if ($node !== FALSE) {
      // An existing school node was found.
      if ($this->importerHelper->compareSchoolNode($node, $line) == FALSE) {
        // Reset the values in the node and save.
        $this->importerHelper->updateSchoolNode($node, $line);
      }
    }
    else {
      // An existing school node was NOT found.
      // Create new school node.
      $node = $this->importerHelper->createSchoolNode($line);

      // Set the values in the node and save.
      $this->importerHelper->updateSchoolNode($node, $line);
    }
  }

  /**
   * Batch is finished proc.
   */
  public function processBatchFinished($success, $results, $operations) {
    if ($success) {
      $this->messenger->addMessage('The School Import is Complete');
      // Dont  variable_set('nys_school_importer_csvupload', '');.
    }
    else {
      $error_operation = reset($operations);
      $message = $this->t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $this->messenger->addError($message);
    }
  }

  /**
   * Returns list of School Names that can not be made unique with 4 added keys.
   */
  public function getOffendingSchoolNamesMarkup($num_keys) {
    // Get schools where `num_keys` equal or greater than $num_keys.
    $result = $this->connection->query('SELECT `legal_name` FROM `nys_school_names_index` WHERE num_keys >= :num_keys', [':num_keys' => $num_keys]);
    $markup = '<ul id="offending-school-names">';
    foreach ($result as $record) {
      $markup = $markup . '<li>';
      $markup = $markup . $record->legal_name;
      $markup = $markup . '</li>';
    }

    $markup = $markup . '</ul>';
    return $markup;
  }

}
