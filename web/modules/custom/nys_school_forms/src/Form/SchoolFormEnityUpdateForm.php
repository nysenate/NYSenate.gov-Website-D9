<?php

namespace Drupal\nys_school_forms\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\file\FileRepository;
use Drupal\nys_school_forms\SchoolFormsService;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The tempstore object for Delete.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $privateTempStoreDelete;

  /**
   * The tempstore object for Show Student.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $privateTempStoreShow;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
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
        FileRepository $fileRepository,
        PrivateTempStoreFactory $temp_store_factory,
        AccountInterface $current_user
    ) {
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
    $this->currentUser = $current_user;
    $this->privateTempStoreDelete = $temp_store_factory->get('school_form_multiple_delete_confirm');
    $this->privateTempStoreShow = $temp_store_factory->get('school_form_multiple_show_student_confirm');
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
          $container->get('file.repository'),
          $container->get('tempstore.private'),
          $container->get('current_user')
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
  public function buildForm(array $form, FormStateInterface $form_state, $params = []) {
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

    $form['table'] = $this->getTable($params);
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
  public function getTable($params) {
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
    $results = $this->schoolFormsService->getResults($params);
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
        if ($params['form_type']) {
          $school_form_type = $params['form_type'];
        }
        else {
          $parent_node_id = 'none';
          $school_form_type = 'Submitted directly from webform';
        }
        $table_results[$result['student']['student_submission'] . '-' . $result['submission']->id()] = [
          'school' => new FormattableMarkup(
              '@school_name <br> @street <br> @city, @state, @zipcode', [
                '@school_name' => $school_name,
                '@street' => $school_address['address_line1'],
                '@city' => $school_address['locality'],
                '@state' => $school_address['administrative_area'],
                '@zipcode' => $school_address['postal_code'],
              ]
          ),
          'senator' => $result['senator']->label(),
          'contact_information' => new FormattableMarkup(
              '@contact_name <br> @contact_email', [
                '@contact_name' => $result['submission']->getData()['contact_name'],
                '@contact_email' => $result['submission']->getData()['contact_email'],
              ]
          ),
          'submission' => new FormattableMarkup(
              '<strong>@student_name</strong> <br> @type <br> <a href="@submission">@file_name</a> <br> Show status: @show_status', [
                '@student_name' => $result['student']['student_name'],
                '@type' => $submission_types[$result['student']['submission_type'] ?? NULL] ?? NULL,
                '@submission' => $file_url,
                '@file_name' => $file->getFilename(),
                '@show_status' => $show_status,
              ]
          ),
          'date_submitted' => date('F j, Y', $result['submission']->getCreatedTime()),
          'school_form_type' => $school_form_type,
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
    $table = $form_state->getValue('table');
    $files = [];
    foreach ($table as $key => $value) {
      if ($value) {
        $parts = explode('-', $value);
        $fid = $parts[0];
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        if (!empty($file)) {
          $files[] = $file;
        }
      }
    }
    if ($operation == 'show_student') {
      $this->privateTempStoreShow->set($this->currentUser->id(), $files);
      $url = Url::fromRoute('nys_school_forms.show_student_submission');
      $form_state->setRedirectUrl($url);
    }
    if ($operation == 'delete_submission') {
      $this->privateTempStoreDelete->set($this->currentUser->id(), $files);
      $url = Url::fromRoute('nys_school_forms.delete_submission');
      $form_state->setRedirectUrl($url);
    }
  }

}
