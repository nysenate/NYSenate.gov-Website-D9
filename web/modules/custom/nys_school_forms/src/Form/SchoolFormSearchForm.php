<?php

namespace Drupal\nys_school_forms\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Current route match.
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
    RouteMatchInterface $current_route_match,
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
    $this->currentRouteMatch = $current_route_match;
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'school_form_search_form';
  }

  /**
   * Standardizes the date search ranges based on config and form filter.
   *
   * @param array $params
   *   Expects to find elements for 'from_date' and 'to_date'.
   *
   * @return array
   *   With elements for 'begin' and 'end', using "Y-m-d" format.
   */
  protected function resolveDates(array $params = []): array {
    $type = strtolower(preg_replace('/\W/', '_', $params['form_type'] ?? ''));

    $config = $this->config('nys_school_forms.config');
    $config_dates = $config->get('default_search_range.' . $type)
      ?? ['begin' => NULL, 'end' => NULL];

    if ($params['from_date'] ?? NULL) {
      $config_dates['begin'] = $params['from_date'];
    }
    if ($params['to_date'] ?? NULL) {
      $config_dates['end'] = $params['to_date'];
    }
    if (!$config_dates['begin']) {
      $config_dates['begin'] = date("Y", time()) . '-01-01';
    }
    if (!$config_dates['end']) {
      $config_dates['end'] = date("Y", time()) . '-12-31';
    }
    return $config_dates;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $params = []) {
    $form['#prefix'] = '<div class="form--inline clearfix">';
    $senator_options = [];
    $senator_options[''] = '- Any -';

    $config_dates = $this->resolveDates($params);

    $senator_terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree('senator');
    foreach ($senator_terms as $senator_term) {
      $senator_options[$senator_term->tid] = $senator_term->name;
    }
    $form['senator'] = [
      '#title' => $this->t('Senator'),
      '#type' => 'select',
      '#options' => $senator_options,
      '#default_value' => html_entity_decode($params['senator'], ENT_QUOTES),
    ];

    $form_type_options = [];
    $form_type_options[''] = '- Any -';

    $form['school'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School'),
      '#autocomplete_route_name' => 'nys_school_forms.autocomplete.school',
      '#autocomplete_route_parameters' => ['form_type' => $params['form_type']],
      '#default_value' => html_entity_decode($params['school'], ENT_QUOTES),
    ];
    $form['teacher_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Teacher Name'),
      '#autocomplete_route_name' => 'nys_school_forms.autocomplete.teacher',
      '#autocomplete_route_parameters' => ['form_type' => $params['form_type']],
      '#default_value' => html_entity_decode($params['teacher_name'], ENT_QUOTES),
    ];
    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => $config_dates['begin'],
    ];
    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => $config_dates['end'],
    ];
    $form['sort_by'] = [
      '#title' => $this->t('Sort By'),
      '#type' => 'select',
      '#options' => [
        'date' => $this->t('Sort by date submitted'),
        'student' => $this->t('Student Name'),
      ],
      '#default_value' => html_entity_decode($params['sort_by'], ENT_QUOTES),
    ];

    $form['sort_order'] = [
      '#title' => $this->t('Order'),
      '#type' => 'select',
      '#options' => [
        'desc' => $this->t('Desc'),
        'asc' => $this->t('ASC'),
      ],
      '#default_value' => html_entity_decode($params['sort_order'], ENT_QUOTES),
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
    $sort_order = $form_state->getValue('sort_order');
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    $url = Url::fromRoute(
      $this->currentRouteMatch->getRouteName(), [], [
        'query' => [
          'senator' => $senator,
          'school' => $school,
          'teacher_name' => $teacher_name,
          'from_date' => $from_date,
          'to_date' => $to_date,
          'sort_by' => $sort_by,
          'sort_order' => $sort_order,
        ],
      ]
    );
    $form_state->setRedirectUrl($url);
  }

}
