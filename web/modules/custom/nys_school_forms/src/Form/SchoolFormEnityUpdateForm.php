<?php

namespace Drupal\nys_school_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
use Drupal\Component\Render\FormattableMarkup;

/**
 * Builds a Form for search school form submissions.
 *
 * @internal
 */
class SchoolFormEnityUpdateForm extends FormBase {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'school_form_entity_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $senator = NULL, string $form_type = NULL, string $school = NULL, string $teacher_name = NULL, string $sort_by = NULL, string $order = NULL) {

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
      '#name' => 'apply',
    ];

    $form['table'] = $this->view();
    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
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

    // Initialize the pager.
    $page = $this->pagerParam->findPage();
    $num_per_page = 150;
    $headers = [
      'school' => 'SCHOOL',
      'senator' => 'SENATOR',
      'contact_information' => 'CONTACT INFORMATION',
      'submission' => 'SUBMISSION',
      'date_submitted' => 'DATE SUBMITTED',
      'school_form_type' => 'SCHOOL FORM TYPE',
    ];
    $offset = $num_per_page * $page;
    $num_results = 0;
    $results = [];
    $table_results = [];
    $query = $this->entityTypeManager->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', 'school_form');
    if ($sort_by == 'date') {
      if ($order) {
        $query->sortBy('completed', $order);
      }
      else {
        $query->sortBy('completed', 'DESC');
      }
    }
    $query_results = $query->execute();
    foreach ($query_results as $query_result) {
      $submission = $this->entityTypeManager->getStorage('webform_submission')->load($query_result);
      $parent_node = $submission->getSourceEntity();
      if ($form_type && $form_type != $parent_node->get('field_school_form_type')->getValue()[0]['target_id']) {
        continue;
      }
      $submission_data = $submission->getData();
      $school_node = $this->entityTypeManager->getStorage('node')->load($submission_data['school_name']);
      if ($school && $school != $school_node->label()) {
        continue;
      }
      $district = $school_node->get('field_district')->entity;
      $school_senator = $district->get('field_senator')->entity;
      if ($senator && $senator != $school_senator->id()) {
        continue;
      }
      if ($teacher_name && $teacher_name != $submission_data['contact_name']) {
        continue;
      }

      foreach ($submission_data['attach_your_submission'] as $student) {
        $results[strtoupper($student['student_name'])] = [
          'school' => $school_node->label(),
          'school_node' => $school_node,
          'senator' => $school_senator->label(),
          'contact_information' => $submission_data['contact_name'],
          'submission_data' => $submission_data,
          'student' => $student,
          'date_submitted' => "hello",
          'school_form_type' => $parent_node->get('field_school_form_type')->entity->label(),
        ];
      }
      if ($sort_by == 'student') {
        ksort($results, SORT_NATURAL);
        $results = array_values($results);
        if ($order == 'desc') {
          // Reverse the array if sort is descending.
          $results = array_reverse($results);
        }
      }
      else {
        $results = array_values($results);
      }
      // Transform Results into Table.
      $i = 0;
      foreach ($results as $key => $result) {
        if ($key >= $offset && $i < $num_per_page) {
          $school_address = $result['school_node']->get('field_school_address')->getValue()[0];
          $school_name = $result['school_node']->label();
          $table_results[$result['student']['student_submission']] = [
            'school' => new FormattableMarkup('@school_name <br> @street <br> @city, @state, @zipcode', [
              '@school_name' => $school_name,
              '@street' => $school_address['address_line1'],
              '@city' => $school_address['locality'],
              '@state' => $school_address['administrative_area'],
              '@zipcode' => $school_address['postal_code'],
            ]),
            'senator' => $result['senator'],
            'contact_information' => new FormattableMarkup('@contact_name <br> @contact_email', [
              '@contact_name' => $result['submission_data']['contact_name'],
              '@contact_email' => $result['submission_data']['contact_email'],
            ]),
            'submission' => new FormattableMarkup('@student_name <br> @type <br> @submission', [
              '@student_name' => $result['student']['student_name'],
              '@type' => $result['student']['submission_type'],
              '@submission' => $result['student']['student_submission'],
            ]),
            'date_submitted' => "hello",
            'school_form_type' => $result['school_form_type'],
          ];
          $i++;
        }
      }
    }
    $num_results = count($results);
    $table = [
      '#type' => 'tableselect',
      '#title' => $this->t('Users'),
      '#header' => $headers,
      '#options' => $table_results,
    ];
    $this->pagerManager->createPager($num_results, $num_per_page);

    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute('nys_school_forms.school_forms', [], []);
    $form_state->setRedirectUrl($url);
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

}
