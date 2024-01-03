<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\file\Entity\File;
use Drupal\nys_school_forms\SchoolFormsService;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route controller for School Form submissions.
 */
class SchoolFormsController extends ControllerBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public RequestStack $request;

  /**
   * ModuleInstaller.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Drupal pager parameters interface.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected PagerParametersInterface $pagerParam;

  /**
   * Drupal pager manager interface.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected PagerManagerInterface $pagerManager;

  /**
   * Drupal form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Drupal alias manager interface.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $aliasManager;

  /**
   * StreamWrapperManager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected StreamWrapperManager $streamWrapperManager;

  /**
   * School Forms Service.
   *
   * @var \Drupal\nys_school_forms\SchoolFormsService
   */
  protected SchoolFormsService $schoolFormsService;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = $container->get('messenger');
    $instance->formBuilder = $container->get('form_builder');
    $instance->request = $container->get('request_stack');
    $instance->database = $container->get('database');
    $instance->pagerParam = $container->get('pager.parameters');
    $instance->pagerManager = $container->get('pager.manager');
    $instance->aliasManager = $container->get('path_alias.manager');
    $instance->streamWrapperManager = $container->get('stream_wrapper_manager');
    $instance->schoolFormsService = $container->get('nys_school_forms.school_forms');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * Creates a render array for the school forms page.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function view($form_type = NULL): array {
    // Fetch, sanitize, and build the query from parameters.
    $params = $this->buildFormParameters();
    if ($form_type) {
      $params['form_type'] = $form_type;
    }
    return [
      '#theme' => 'school_forms',
      '#search_form' => $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormSearchForm', $params),
      '#entity_update_form' => $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormEntityUpdateForm', $params),
      '#export_link' => '/admin/school-forms/export?' . http_build_query($params),
    ];
  }

  /**
   * Sanitize query string.
   *
   * @param string|null $query_string
   *   Raw string for a query.
   *
   * @return string
   *   Sanitized
   */
  protected function sanitizeQuery(?string $query_string): string {
    return htmlspecialchars(trim((string) $query_string));
  }

  /**
   * Builds the form parameters based on the current request.
   */
  protected function buildFormParameters(): array {
    $req = $this->request->getCurrentRequest();
    $fields = [
      'form_type',
      'senator',
      'school',
      'teacher_name',
      'from_date',
      'to_date',
      'sort_by',
      'sort_order',
    ];
    $ret = [];
    foreach ($fields as $val) {
      $ret[$val] = urldecode($this->sanitizeQuery($req->get($val)));
    }

    return $ret;
  }

  /**
   * Exports the form as a CSV file.
   */
  public function exportCsv(): Response {
    $params = $this->buildFormParameters();
    $results = $this->schoolFormsService->getResults($params);
    $handle = fopen('php://temp', 'w+');
    fputcsv(
      $handle,
      [
        'Date submitted',
        'Student\'s Name',
        'Grade',
        'Teacher',
        'School Name',
        'Street',
        'City, State',
        'Zip Code',
        'School Phone',
        'Senator',
        'District Number',
        'Student Submission',
      ]
    );

    foreach ($results as $result) {
      $file = File::load($result['student']['student_submission']);
      $uri = $file->getFileUri();
      $file_string = $this->fileUrlGenerator->generateAbsoluteString($uri);
      $school_address = $result['school_node']->get('field_school_address')
        ->getValue()[0];
      $line = [
        date('F j, Y', $result['submission']->getCreatedTime()),
        $result['student']['student_name'],
        $result['submission']->getData()['grade'],
        $result['submission']->getData()['contact_name'],
        $result['school_node']->label(),
        $school_address['address_line1'],
        $school_address['locality'] . ',' . $school_address['administrative_area'],
        $school_address['postal_code'],
        $result['school_node']->get('field_school_ceo_phone')
          ->getValue()[0]['value'],
        $result['senator']->label(),
        $result['school_node']->get('field_district')->entity->label(),
        $file_string,
      ];
      fputcsv($handle, $line);
    }
    rewind($handle);
    $csv_data = stream_get_contents($handle);
    fclose($handle);
    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="student-export.csv"');
    $response->setContent($csv_data);
    return $response;
  }

  /**
   * Controller method for generating webform submissions.
   */
  public function generateArchiveWebformSubmissions($form_type = 'earth_day', $year = '2019'): array {
    // Get the webform storage.  If no joy, report and exit.
    try {
      $webformSubmissionStorage = $this->entityTypeManager->getStorage('webform_submission');
    }
    catch (\Throwable) {
      $this->getLogger('nys_school_forms')
        ->error('Failed to instantiate entity storage for webform_submission objects');
      $this->messenger->addError('There was an error while attempting to generate a submission.');
      return [];
    }

    $webform_type = match ($form_type) {
      'thankful' => 'school_form_thanksgiving',
      'earth_day' => 'school_form_earth_day',
    };

    // Query the last 5 webform submissions with webform ID = form type.
    $submission_ids = $webformSubmissionStorage->getQuery()
      ->condition('webform_id', $webform_type)
      ->range(0, 5)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->execute();
    $start = $year;
    $errors = 0;
    $submissions = $webformSubmissionStorage->loadMultiple($submission_ids);

    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    foreach ($submissions as $submission) {
      // Modify the submission as needed.
      if (!empty($submission)) {
        if ($start >= '2022') {
          $start = '2022';
        }
        $new_created_date = strtotime($start . '-01-01 00:00:00');
        $submission->setCreatedTime($new_created_date);
        try {
          $submission->save();
        }
        catch (\Throwable $e) {
          $this->getLogger('nys_school_forms')
            ->error('An error occurred while attempting to save a submission', ['@msg' => $e->getMessage()]);
          $this->messenger->addError('An error occurred while saving a submission.');
          $errors++;
        }
      }
      $start++;
    }

    $markup = $errors
      ? $errors . " occurred while modifying created dates."
      : 'Successfully modified created dates for he last ' . count($submission_ids) . ' webform submissions.';

    return [
      '#type' => 'markup',
      '#markup' => $markup,
    ];
  }

}
