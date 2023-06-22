<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
  public $request;

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
  protected $database;

  /**
   * Drupal messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal pager parameters interface.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParam;

  /**
   * Drupal pager manager interface.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

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
  protected $aliasManager;

  /**
   * StreamWrapperManager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * Entity Type Mananger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * School Forms Service.
   *
   * @var \Drupal\nys_school_forms\SchoolFormsService
   */
  protected $schoolFormsService;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->request = $container->get('request_stack');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->database = $container->get('database');
    $instance->messenger = $container->get('messenger');
    $instance->pagerParam = $container->get('pager.parameters');
    $instance->pagerManager = $container->get('pager.manager');
    $instance->formBuilder = $container->get('form_builder');
    $instance->aliasManager = $container->get('path_alias.manager');
    $instance->streamWrapperManager = $container->get('stream_wrapper_manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
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
  public function view($form_type = NULL) {
    // Fetch, sanitize, and build the query from parameters.
    $senator = $this->sanitizeQuery($this->request->getCurrentRequest()->get('senator'));
    $school = $this->sanitizeQuery($this->request->getCurrentRequest()->get('school'));
    $teacher_name = $this->sanitizeQuery($this->request->getCurrentRequest()->get('teacher_name'));
    $from_date = $this->sanitizeQuery($this->request->getCurrentRequest()->get('from_date'));
    $to_date = $this->sanitizeQuery($this->request->getCurrentRequest()->get('to_date'));
    $sort_by = $this->sanitizeQuery($this->request->getCurrentRequest()->get('sort_by'));
    $sort_order = $this->sanitizeQuery($this->request->getCurrentRequest()->get('sort_order'));
    $build = [];
    $build['#theme'] = 'school_forms';

    $params = [
      'form_type' => $form_type,
      'senator' => urldecode($senator),
      'school' => urldecode($school),
      'teacher_name' => urldecode($teacher_name),
      'from_date' => strtotime(urldecode($from_date)),
      'to_date' => strtotime(urldecode($to_date)),
      'sort_by' => urldecode($sort_by),
      'sort_order' => urldecode($sort_order),
    ];

    $build['#search_form'] = $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormSearchForm', $params);
    $build['#entity_update_form'] = $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormEnityUpdateForm', $params);
    $build['#export_link'] = '/admin/school-forms/export?senator=' . urldecode($senator) . '&form_type=' . $form_type . '&school=' . urldecode($school) . '&teacher_name=' . urldecode($teacher_name) . '&from_date=' . urldecode($from_date) . '&to_date=' . urldecode($to_date) . '&sort_by=' . urldecode($sort_by) . '&sort_order=' . urldecode($sort_order);
    return $build;
  }

  /**
   * Sanitize query string.
   *
   * @param string $query
   *   Raw query.
   *
   * @return string
   *   Sanitized
   */
  protected function sanitizeQuery($query) {
    $query = trim($query);
    $filtered_query = filter_var($query, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    return $filtered_query ? $filtered_query : $query;
  }

  /**
   * {@inheritdoc}
   */
  public function exportCsv() {
    // Fetch, sanitize, and build the query from parameters.
    $senator = $this->sanitizeQuery($this->request->getCurrentRequest()->get('senator'));
    $form_type = $this->sanitizeQuery($this->request->getCurrentRequest()->get('form_type'));
    $school = $this->sanitizeQuery($this->request->getCurrentRequest()->get('school'));
    $teacher_name = $this->sanitizeQuery($this->request->getCurrentRequest()->get('teacher_name'));
    $from_date = $this->sanitizeQuery($this->request->getCurrentRequest()->get('from_date'));
    $to_date = $this->sanitizeQuery($this->request->getCurrentRequest()->get('to_date'));
    $sort_by = $this->sanitizeQuery($this->request->getCurrentRequest()->get('sort_by'));
    $sort_order = $this->sanitizeQuery($this->request->getCurrentRequest()->get('sort_order'));

    $params = [
      'form_type' => $form_type,
      'senator' => urldecode($senator),
      'school' => urldecode($school),
      'teacher_name' => urldecode($teacher_name),
      'from_date' => urldecode($from_date),
      'to_date' => urldecode($to_date),
      'sort_by' => urldecode($sort_by),
      'sort_order' => urldecode($sort_order),
    ];
    $results = $this->schoolFormsService->getResults($params);
    $handle = fopen('php://temp', 'w+');
    fputcsv(
          $handle, [
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
          ], ','
      );

    foreach ($results as $result) {
      $file = File::load($result['student']['student_submission']);
      $uri = $file->getFileUri();
      $file_string = $this->fileUrlGenerator->generateAbsoluteString($uri);
      $school_address = $result['school_node']->get('field_school_address')->getValue()[0];
      $line = [
        date('F j, Y', $result['submission']->getCreatedTime()),
        $result['student']['student_name'],
        $result['submission']->getData()['grade'],
        $result['submission']->getData()['contact_name'],
        $result['school_node']->label(),
        $school_address['address_line1'],
        $school_address['locality'] . ',' . $school_address['administrative_area'],
        $school_address['postal_code'],
        $result['school_node']->get('field_school_ceo_phone')->getValue()[0]['value'],
        $result['senator']->label(),
        $result['school_node']->get('field_district')->entity->label(),
        $file_string,
      ];
      fputcsv($handle, $line, ',');
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
  public function generateArchiveWebformSubmissions($form_type = 'earth_day', $year = '2019') {

    $webformSubmissionStorage = $this->entityTypeManager->getStorage('webform_submission');
    $webform_type = match ($form_type) {
      'thankful' => 'school_form_thanksgiving',
            'earth_day' => 'school_form_earth_day',
    };
    // Query the last 5 webform submissions with webform ID = form type.
    $query = $webformSubmissionStorage->getQuery()
      ->condition('webform_id', $webform_type)
      ->range(0, 5)
      ->sort('created', 'DESC');
    $submission_ids = $query->execute();
    $start = $year;
    foreach ($submission_ids as $submission_id) {
      if ($start >= '2022') {
        $start = '2022';
      }
      $new_created_date = strtotime($start . '-01-01 00:00:00');
      // Load the submission entity.
      $submission = $webformSubmissionStorage->load($submission_id);
      // Modify the submission as needed.
      if (!empty($submission)) {
        $submission->setCreatedTime($new_created_date);
        $submission->save();
      }
      // Save the submission.
      $submission->save();
      $start++;
    }
    $markup = 'The last 5 webform submissions successfully modified created dates.';

    $build = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $build;
  }

}
