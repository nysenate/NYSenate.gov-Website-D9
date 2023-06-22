<?php

namespace Drupal\nys_school_importer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_school_importer\ImporterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The AnalyzeController controller class.
 */
class AnalyzeController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Default object for nys_school_importer.importer service.
   *
   * @var \Drupal\nys_school_importer\ImporterHelper
   */
  protected $importerHelper;

  /**
   * Default object for database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Default object for extension.path.resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * AnalyzeController constructor method.
   *
   * @param \Drupal\nys_school_importer\ImporterHelper $importer_helper
   *   The importer helper class.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection class.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger class.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $path_resolver
   *   The path resolver class.
   */
  public function __construct(
        ImporterHelper $importer_helper,
        Connection $connection,
        Messenger $messenger,
        ExtensionPathResolver $path_resolver
    ) {
    $this->importerHelper = $importer_helper;
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->pathResolver = $path_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container_interface) {
    return new static(
          $container_interface->get('nys_school_importer.importer'),
          $container_interface->get('database'),
          $container_interface->get('messenger'),
          $container_interface->get('extension.path.resolver')
      );
  }

  /**
   * Analyzes all the school names in the nys_school_names table.
   */
  public function analyzePage() {
    // Seup the batch.
    $batch = [
      'operations' => [],
      'finished' => 'analyzeBatchFinished',
      'title' => $this->t('Analyzing School Names'),
      'init_message' => $this->t('Import is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Import has encountered an error.'),
    ];

    // Clear out the nys_school_names_index table.
    $this->importerHelper->clearNysSchoolNamesIndex();
    $progress = 0;
    // How many to process for each run.
    $limit = 1;

    // Iterate thru all the rows in the nys_school_names table.
    $result = $this->connection->query('SELECT * FROM `nys_school_names` WHERE 1');
    foreach ($result as $record) {
      $batch['operations'][] = [
        'analyzeProcess',
        [$progress, $limit, $record],
      ];
      $progress = $progress + 1;
    }

    batch_set($batch);
    batch_process('admin/nys-school-import');
  }

  /**
   * This is what runs multiple times per batch.
   */
  public function analyzeProcess($progress, $limit, $record, &$context) {
    // Calculate the index.
    $this->importerHelper->calculateNameIndex($record);
  }

  /**
   * Analyze batch finished.
   */
  public function analyzeBatchFinished($success, $results, $operations) {
    // If run was successful.
    if ($success) {
      // Perform final adjustments and override
      // number of keys needed for a name.
      $this->analyzeExceptions();
      $this->messenger->addMessage('Import is complete');
    }
    else {
      $error_operation = reset($operations);
      $message = $this->t(
            'An error occurred while processing %error_operation with arguments: @arguments', [
              '%error_operation' => $error_operation[0],
              '@arguments' => var_export($error_operation[1], TRUE),
            ]
        );
      $this->messenger->addError($message);
    }
  }

  /**
   * Override and fix problems.
   */
  public function analyzeExceptions() {
    // Get the path for the exceptions file and perform.
    $exception_file_path = $this->pathResolver->getPath('module', 'nys_school_importer') . '/nys_school_importer_mapping.json';
    $exception_list = json_decode(file_get_contents($exception_file_path));

    if ($exception_list !== NULL && $exception_list !== FALSE && is_array($exception_list) && count($exception_list) > 0) {
      foreach ($exception_list as $exception) {
        $legal_name = $exception->legal_name;
        $num_keys = $exception->num_keys;
        $this->importerHelper->createSchoolNameIndex($legal_name, $num_keys);
      }
    }
  }

}
