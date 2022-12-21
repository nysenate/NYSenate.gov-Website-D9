<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Form\FormBuilder;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\nys_school_forms\SchoolFormsService;
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
   * Class constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module Handler.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal Messenger.
   * @param \Drupal\Core\Pager\PagerParametersInterface $pager_param
   *   Pager.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   Pager manager.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   Form Builder.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   Alias Manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapperManager
   *   The StreamWrapperManager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\nys_school_forms\SchoolFormsService $schoolFormsService
   *   The School Forms Service.
   */
  public function __construct(
    RequestStack $request,
    ModuleHandler $moduleHandler,
    Connection $database,
    MessengerInterface $messenger,
    PagerParametersInterface $pager_param,
    PagerManagerInterface $pager_manager,
    FormBuilder $form_builder,
    AliasManagerInterface $alias_manager,
    StreamWrapperManager $streamWrapperManager,
    EntityTypeManager $entityTypeManager,
    SchoolFormsService $schoolFormsService) {
    $this->request = $request;
    $this->moduleHandler = $moduleHandler;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->pagerParam = $pager_param;
    $this->pagerManager = $pager_manager;
    $this->formBuilder = $form_builder;
    $this->aliasManager = $alias_manager;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->schoolFormsService = $schoolFormsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('database'),
      $container->get('messenger'),
      $container->get('pager.parameters'),
      $container->get('pager.manager'),
      $container->get('form_builder'),
      $container->get('path_alias.manager'),
      $container->get('stream_wrapper_manager'),
      $container->get('entity_type.manager'),
      $container->get('nys_school_forms.school_forms')
    );
  }

  /**
   * Creates a render array for the school forms page.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function view() {
    // Fetch, sanitize, and build the query from parameters.
    $senator = $this->sanitizeQuery($this->request->getCurrentRequest()->get('senator'));
    $form_type = $this->sanitizeQuery($this->request->getCurrentRequest()->get('form_type'));
    $school = $this->sanitizeQuery($this->request->getCurrentRequest()->get('school'));
    $teacher_name = $this->sanitizeQuery($this->request->getCurrentRequest()->get('teacher_name'));
    $from_date = $this->sanitizeQuery($this->request->getCurrentRequest()->get('from_date'));
    $to_date = $this->sanitizeQuery($this->request->getCurrentRequest()->get('to_date'));
    $sort_by = $this->sanitizeQuery($this->request->getCurrentRequest()->get('sort_by'));
    $order = $this->sanitizeQuery($this->request->getCurrentRequest()->get('order'));
    $build = [];
    $build['#theme'] = 'school_forms';

    $build['#search_form'] = $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormSearchForm', urldecode($senator), urldecode($form_type), urldecode($school), urldecode($teacher_name), urldecode($from_date), urldecode($to_date), urldecode($sort_by), urldecode($order));
    $build['#entity_update_form'] = $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormEnityUpdateForm', urldecode($senator), urldecode($form_type), urldecode($school), urldecode($teacher_name), urldecode($from_date), urldecode($to_date), urldecode($sort_by), urldecode($order));
    $build['#export_link'] = '/admin/school-forms/export?senator=' . urldecode($senator) . '&form_type=' . urldecode($form_type) . '&school=' . urldecode($school) . '&teacher_name=' . urldecode($teacher_name) . '&from_date=' . urldecode($from_date) . '&to_date=' . urldecode($to_date) . '&sort_by=' . urldecode($sort_by) . '&order=' . urldecode($order);
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
    $order = $this->sanitizeQuery($this->request->getCurrentRequest()->get('order'));
    $results = $this->schoolFormsService->getResults($senator, $form_type, $school, $teacher_name, $from_date, $to_date, $sort_by, $order);
    $handle = fopen('php://temp', 'w+');
    fputcsv($handle, [
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
    ], ',');

    foreach ($results as $result) {
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

}
