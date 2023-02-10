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

/**
 * Builds a Form for search school form submissions.
 *
 * @internal
 */
class SchoolFormSearchForm extends FormBase {

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
    return 'school_form_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $senator = NULL, string $school = NULL, string $teacher_name = NULL, string $from_date = NULL, string $to_date = NULL, string $sort_by = NULL, string $order = NULL) {
    $form['#prefix'] = '<div class="form--inline clearfix">';
    $senator_options = [];
    $senator_options[''] = '- Any -';

    $senator_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('senator');
    foreach ($senator_terms as $senator_term) {
      $senator_options[$senator_term->tid] = $senator_term->name;
    }
    $form['senator'] = [
      '#title' => $this->t('Senator'),
      '#type' => 'select',
      '#options' => $senator_options,
      '#default_value' => html_entity_decode($senator, ENT_QUOTES),
    ];

    $form_type_options = [];
    $form_type_options[''] = '- Any -';

    $form['school'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School'),
      '#autocomplete_route_name' => 'nys_school_forms.autocomplete.school',
      '#default_value' => html_entity_decode($school, ENT_QUOTES),
    ];
    $form['teacher_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Teacher Name'),
      '#autocomplete_route_name' => 'nys_school_forms.autocomplete.teacher',
      '#default_value' => html_entity_decode($teacher_name, ENT_QUOTES),
    ];
    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => html_entity_decode($from_date, ENT_QUOTES),
    ];
    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => html_entity_decode($to_date, ENT_QUOTES),
    ];
    $form['sort_by'] = [
      '#title' => $this->t('Sort By'),
      '#type' => 'select',
      '#options' => [
        'date' => $this->t('Sort by date submitted'),
        'student' => $this->t('Student Name'),
      ],
      '#default_value' => html_entity_decode($sort_by, ENT_QUOTES),
    ];

    $form['order'] = [
      '#title' => $this->t('Order'),
      '#type' => 'select',
      '#options' => [
        'desc' => $this->t('Desc'),
        'asc' => $this->t('ASC'),
      ],
      '#default_value' => html_entity_decode($order, ENT_QUOTES),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#name' => 'apply',
      '#prefix' => '<div data-drupal-selector="edit-actions" class="form-actions">',
      '#suffix' => '</div>',
    ];
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $senator = $form_state->getValue('senator');
    $school = $form_state->getValue('school');
    $teacher_name = $form_state->getValue('teacher_name');
    $sort_by = $form_state->getValue('sort_by');
    $order = $form_state->getValue('order');
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    $url = Url::fromRoute('nys_school_forms.school_forms', [], [
      'query' => [
        'senator' => $senator,
        'school' => $school,
        'teacher_name' => $teacher_name,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'sort_by' => $sort_by,
        'order' => $order,
      ],
    ]);
    $form_state->setRedirectUrl($url);
  }

}
