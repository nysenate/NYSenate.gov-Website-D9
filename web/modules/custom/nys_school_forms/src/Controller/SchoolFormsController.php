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
    EntityTypeManager $entityTypeManager) {
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
      $container->get('entity_type.manager')
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
    $sort_by = $this->sanitizeQuery($this->request->getCurrentRequest()->get('sort_by'));
    $order = $this->sanitizeQuery($this->request->getCurrentRequest()->get('order'));
    $build = [];
    $build['#theme'] = 'school_forms';

    $build["#search_form"] = $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormSearchForm', urldecode($senator), urldecode($form_type), urldecode($school), urldecode($teacher_name), urldecode($sort_by), urldecode($order));
    $build["#entity_update_form"] = $this->formBuilder->getForm('Drupal\nys_school_forms\Form\SchoolFormEnityUpdateForm', urldecode($senator), urldecode($form_type), urldecode($school), urldecode($teacher_name), urldecode($sort_by), urldecode($order));

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
    $build = $this->adminPage();
    $results = $build['#csv_results'];
    $handle = fopen('php://temp', 'w+');
    fputcsv($handle, [
      'Date submitted',
      'Students Name',
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

    foreach ($results as $line) {
      $line['user'] = strip_tags($line['user']);
      fputcsv($handle, $line, ',');
    }
    rewind($handle);
    $csv_data = stream_get_contents($handle);
    fclose($handle);
    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="user-revision-report.csv"');
    $response->setContent($csv_data);
    return $response;
  }

}
