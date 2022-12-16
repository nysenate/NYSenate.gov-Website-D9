<?php

namespace Drupal\nys_school_forms\Form;

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
use Drupal\Core\File\FileUrlGenerator;
use Drupal\file\FileInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileSystem;
use Drupal\file\FileRepository;
use Drupal\nys_school_forms\SchoolFormsService;
use Drupal\Core\Form\ConfirmFormBase;

/**
 * Builds a Form for search school form submissions.
 *
 * @internal
 */
class SchoolFormEnityUpdateForm extends ConfirmFormBase {

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
   * File url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * File repository service.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

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
   * @param \Drupal\Core\File\FileUrlGenerator $fileUrlGenerator
   *   File url generator service.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   File system service.
   * @param \Drupal\file\FileRepository $fileRepository
   *   File repository service.
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
    SchoolFormsService $schoolFormsService,
    FileUrlGenerator $fileUrlGenerator,
    FileSystem $fileSystem,
    FileRepository $fileRepository) {
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
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;
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
      $container->get('nys_school_forms.school_forms'),
      $container->get('file_url_generator'),
      $container->get('file_system'),
      $container->get('file.repository')
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
  public function getCancelUrl() {
    return new Url('nys_school_forms.school_forms');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sture you want to delele this submission?');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $senator = NULL, string $form_type = NULL, string $school = NULL, string $teacher_name = NULL, string $sort_by = NULL, string $order = NULL) {
    $form['operations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Operations'),
      '#tree' => TRUE,
    ];
    $form['operations']['action_type'] = [
      '#title' => $this->t('Operation'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('- Choose an Operation -'),
        'show_student' => $this->t('Show Student'),
        'delete_submission' => $this->t('Delete Submission'),
      ],
      '#title_display' => FALSE,
    ];
    $form['operations']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
      '#name' => 'apply',
    ];

    $form['table'] = $this->getTable($senator, $form_type, $school, $teacher_name, $sort_by, $order);
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
  public function getTable($senator, $form_type, $school, $teacher_name, $sort_by, $order) {
    // Initialize the pager.
    $page = $this->pagerParam->findPage();
    $num_per_page = 150;
    $table_headers = [
      'school' => 'SCHOOL',
      'senator' => 'SENATOR',
      'contact_information' => 'CONTACT INFORMATION',
      'submission' => 'SUBMISSION',
      'date_submitted' => 'DATE SUBMITTED',
      'school_form_type' => 'SCHOOL FORM TYPE',
    ];
    $offset = $num_per_page * $page;
    $num_results = 0;
    $results = $this->schoolFormsService->getResults($senator, $form_type, $school, $teacher_name, $sort_by, $order);
    $table_results = [];
    // Transform Results into Table.
    $i = 0;
    foreach ($results as $key => $result) {
      if ($key >= $offset && $i < $num_per_page) {
        $school_address = $result['school_node']->get('field_school_address')->getValue()[0];
        $school_name = $result['school_node']->label();
        $file = $this->entityTypeManager->getStorage('file')->load($result['student']['student_submission']);
        $file_uri = $file->getFileUri();
        // Set show status based on file scheme.
        $scheme = $this->streamWrapperManager->getScheme($file_uri);
        if ($scheme == 'public') {
          $show_status = 'yes';
        }
        else {
          $show_status = 'no';
        }
        $file_url = $this->fileUrlGenerator->generateString($file_uri);
        // Helper array to get submission type values.
        $submission_types = [
          0 => 'a work of art',
          1 => 'an essay',
          2 => 'a poem',
        ];
        $table_results[$result['student']['student_submission'] . '-' . $result['submission']->id() . '-' . $result['parent_node']->id()] = [
          'school' => new FormattableMarkup('@school_name <br> @street <br> @city, @state, @zipcode', [
            '@school_name' => $school_name,
            '@street' => $school_address['address_line1'],
            '@city' => $school_address['locality'],
            '@state' => $school_address['administrative_area'],
            '@zipcode' => $school_address['postal_code'],
          ]),
          'senator' => $result['senator']->label(),
          'contact_information' => new FormattableMarkup('@contact_name <br> @contact_email', [
            '@contact_name' => $result['submission']->getData()['contact_name'],
            '@contact_email' => $result['submission']->getData()['contact_email'],
          ]),
          'submission' => new FormattableMarkup('<strong>@student_name</strong> <br> @type <br> <a href="@submission">@file_name</a> <br> Show status: @show_status', [
            '@student_name' => $result['student']['student_name'],
            '@type' => $submission_types[$result['student']['submission_type']],
            '@submission' => $file_url,
            '@file_name' => $file->getFilename(),
            '@show_status' => $show_status,
          ]),
          'date_submitted' => date('F j, Y', $result['submission']->getCreatedTime()),
          'school_form_type' => $result['parent_node']->get('field_school_form_type')->entity->label(),
        ];
        $i++;
      }
    }
    $num_results = count($results);
    $table = [
      '#type' => 'tableselect',
      '#title' => $this->t('Users'),
      '#header' => $table_headers,
      '#options' => $table_results,
    ];
    $this->pagerManager->createPager($num_results, $num_per_page);

    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue(['operations', 'action_type']);
    if ($operation == 'show_student') {
      $table = $form_state->getValue('table');
      foreach ($table as $key => $value) {
        if ($value) {
          $parts = explode('-', $value);
          $fid = $parts[0];
          $sid = $parts[1];
          $nid = $parts[2];
          $file = $this->entityTypeManager->getStorage('file')->load($fid);
          $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
          $submission_timestamp = $submission->getCreatedTime();
          $node = $this->entityTypeManager->getStorage('node')->load($nid);
          if ($file instanceof FileInterface) {
            $directory = 'public://' . $node->get('field_school_form_type')->entity->label() . '/' . $node->id() . '/' . date('Y', $submission_timestamp) . '/';
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
            $destination = $directory . $file->getFilename();
            $this->fileRepository->move($file, $destination);
          }
        }
      }
    }
    if ($operation == 'delete_submission') {
      $table = $form_state->getValue('table');
      foreach ($table as $key => $value) {
        if ($value) {
          $parts = explode('-', $value);
          $fid = $parts[0];
          $file = $this->entityTypeManager->getStorage('file')->load($fid);
          if (!empty($file)) {
            $file->delete();
          }
        }
      }
    }
    $url = Url::fromRoute('nys_school_forms.school_forms', [], []);
    $form_state->setRedirectUrl($url);
  }

}
