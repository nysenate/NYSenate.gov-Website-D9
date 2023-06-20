<?php

namespace Drupal\nys_school_importer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\State;
use Drupal\nys_school_importer\ImporterHelper;
use Drupal\nys_school_importer\SchoolImporterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * Default object for nys_school_importer.school_importer service.
   *
   * @var \Drupal\nys_school_importer\SchoolImporterHelper
   */
  protected $schoolImporterHelper;

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
   * @param \Drupal\nys_school_importer\SchoolImporterHelper $school_importer_helper
   *   The school importer helper service.
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
        SchoolImporterHelper $school_importer_helper,
        State $state,
        FormBuilderInterface $form_builder_interface,
        RendererInterface $renderer_interface,
        Messenger $messenger,
        Connection $connection
    ) {
    $this->importerHelper = $importer_helper;
    $this->schoolImporterHelper = $school_importer_helper;
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
          $container_interface->get('nys_school_importer.school_importer'),
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
        $this->schoolImporterHelper->processImport();
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

}
