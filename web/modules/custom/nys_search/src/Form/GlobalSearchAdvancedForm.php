<?php

namespace Drupal\nys_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nys_search\GlobalSearchAdvancedHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The Search advanced legislation form class.
 */
class GlobalSearchAdvancedForm extends FormBase {
  /**
   * Search Advanced Legislation helper service.
   *
   * @var \Drupal\nys_search\GlobalSearchAdvancedHelper
   */
  protected GlobalSearchAdvancedHelper $helper;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Implements the create() method on the form controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   *   The form object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('nys_search.helper'),
          $container->get('request_stack')
      );
  }

  /**
   * Search Advanced Legislation form constructor.
   *
   * @param \Drupal\nys_search\GlobalSearchAdvancedHelper $helper
   *   The GlobalSearchAdvancedHelper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(GlobalSearchAdvancedHelper $helper, RequestStack $request_stack) {
    $this->helper = $helper;
    $this->requestStack = $request_stack;
  }

  /**
   * Returns a list of Senators.
   *
   * @return array
   *   Senators array.
   */
  public function getSenatorsList(): array {
    $query = \Drupal::database()->select('taxonomy_term__field_senator_name', 's');
    $query->fields('s', ['entity_id', 'field_senator_name_given']);
    $query->addExpression("CONCAT(s.field_senator_name_given,' ',s.field_senator_name_family)", 'senator_full_name');
    $query->orderBy('s.field_senator_name_given');

    $results = [];
    $results = $query->execute()->fetchAllKeyed(0, 2);
    return $results;
  }

  /**
   * Returns a list of committees.
   *
   * @return array
   *   Committee array.
   */
  public function getCommitteeList(): array {
    $committees = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'committees']);

    $result = [];
    $result['all'] = 'Filter By Committee';
    foreach ($committees as $committe) {
      $result[ucwords($committe->label())] = $committe->label();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_search_global_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build your form here.
    $form['#cache']['contexts'][] = 'url.query_args';
    $results_page = $this->helper->isResultsPage();
    $markup = '';
    $form['global_search'] = [
      '#type' => 'block',
      '#attributes' => [
        'class' => ['adv-search-container'],
      ],
    ];
    if (!$results_page) {
      $form['global_search']['global_search_title'] = [
        '#type' => 'item',
        '#markup' => $this->t('<h1>Global Search</h1>'),
      ];
    }
    $args = $this->requestStack->getCurrentRequest()->query->all();

    $form['advanced_search']['advanced_search_text'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $form['senator'] = [
      '#type' => 'select',
      '#options' => ['all' => 'Filter By Senator'] + $this->getSenatorsList(),
      '#default_value' => $args['senator'] ?? NULL,
      '#attributes' => [
        'class' => ['sponsor'],
      ],
    ];

    $form['committee'] = [
      '#type' => 'select',
      '#options' => $this->getCommitteeList(),
      '#default_value' => $args['committee'] ?? NULL,
      '#attributes' => [
        'class' => ['committee'],
      ],
    ];

    $form['type'] = [
      '#type' => 'select',
      '#options' => [
        'all' => t('Filter By Type'),
        'bill' => t('Bills'),
        'resolution' => t('Resolutions'),
        'meeting' => t('Committee Meeting Agendas'),
        'session' => t('Session Calendars'),
        'floor' => t('Session Transcripts'),
        'public_hearing' => t('Public Hearing Transcripts'),
      ],
      '#default_value' => $args['type'] ?? NULL,
      '#attributes' => [
        'class' => ['content-type'],
      ],
    ];

    $form['key'] = [
      '#type' => 'hidden',
      '#default_value' => $args['key'] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';
    $form['#attached']['library'][] = 'nys_search/nys_search';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('SEARCH'),
      '#button_type' => 'small',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $params = [
      'key' => $values['key'] ?: '',
      'senator' => $values['senator'] ?: '',
      'committee' => $values['committee'] ?: '',
      'type' => $values['type'] ?: '',
      'sort_order' => 'DESC',
    ];

    // Filter out any empty values.
    $params = array_filter(
          $params, function ($value) {
              return $value !== '';
          }
      );

    $url = Url::fromRoute('nys_search.gobalSearch', [], ['query' => $params]);

    $form_state->setRedirectUrl($url);
  }

}
