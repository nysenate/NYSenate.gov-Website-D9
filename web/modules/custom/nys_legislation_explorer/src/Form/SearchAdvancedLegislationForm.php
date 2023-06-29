<?php

namespace Drupal\nys_legislation_explorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nys_legislation_explorer\SearchAdvancedLegislationHelper;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The Search advanced legislation form class.
 */
class SearchAdvancedLegislationForm extends FormBase {
  /**
   * Search Advanced Legislation helper service.
   *
   * @var \Drupal\nys_legislation_explorer\SearchAdvancedLegislationHelper
   */
  protected SearchAdvancedLegislationHelper $helper;

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
          $container->get('nys_legislation_explorer.helper'),
          $container->get('request_stack')
      );
  }

  /**
   * Search Advanced Legislation form constructor.
   *
   * @param \Drupal\nys_legislation_explorer\SearchAdvancedLegislationHelper $helper
   *   The SearchAdvancedLegislationHelper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(SearchAdvancedLegislationHelper $helper, RequestStack $request_stack) {
    $this->helper = $helper;
    $this->requestStack = $request_stack;
  }

  /**
   * Returns a list of session years in descending order.
   *
   * Restricts years from the current session year to the earliest session that
   * there is legislation data for (2009).
   *
   * @param int $min_year
   *   Minimum year restriction to fetch session years from.
   *
   * @return array
   *   Sessions years.
   */
  public function getSessionYearList(int $min_year = 2009): array {
    $year = date("Y");
    $session_years = [];
    while ($year >= $min_year) {
      if ($year % 2 != 0) {
        $session_years[] = $year;
      }
      $year--;
    }
    return $session_years;
  }

  /**
   * Returns a list of years in descending order.
   *
   * Restricts years from the current year to the earliest session that
   * there is legislation data for (2009).
   *
   * @param int $min_year
   *   Minimum year restriction to fetch session years from.
   *
   * @return array
   *   years.
   */
  public function getYearList(int $min_year = 2009): array {
    $year = date("Y");
    $years = [];
    while ($year >= $min_year) {

      $years[] = $year;
      $year--;
    }
    return $years;
  }

  /**
   * Generate months options for basic select.
   */
  public function getMonthsOptions() {
    $months = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];

    $options = [];
    for ($i = 0; $i < 12; $i++) {
      $value = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
      $options[$value] = $months[$i];
    }

    return $options;
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
    $result['all'] = 'Any';
    foreach ($committees as $committe) {
      $result[ucwords($committe->label())] = $committe->label();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_search_legislation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build your form here.
    $form['#cache']['contexts'][] = 'url.query_args';
    $results_page = $this->helper->isResultsPage();
    $markup = 'Fill out one or more of the following filter criteria to perform a search.';
    $form['advanced_search'] = [
      '#type' => 'block',
      '#attributes' => [
        'class' => ['adv-search-container'],
      ],
    ];
    if ($results_page) {
      $markup = 'Refine your search further or search for something else.';
    }
    else {
      $form['advanced_search']['advanced_search_title'] = [
        '#type' => 'item',
        '#markup' => $this->t('<h1>Advanced Legislation Search</h1>'),
      ];
    }
    $args = $this->requestStack->getCurrentRequest()->query->all();
    $month_default = '';
    $year_default = '';
    if (!empty($args['date']) || !empty($args['publish_date']) || !empty($args['meeting_date'])) {
      $dates['date'] = $args['date'] ?? NULL;
      $dates['publish_date'] = $args['publish_date'] ?? NULL;
      $dates['meeting_date'] = $args['meeting_date'] ?? NULL;
      foreach ($dates as $date) {
        if ($date !== NULL) {
          $parts = explode("-", $date);
          $month_default = $parts[1];
          $year_default = substr($date, 0, 4);
        }
      }
    }

    $form['advanced_search']['advanced_search_text'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => ('FILTER BY CONTENT TYPE:'),
      '#options' => [
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

    $form['printno'] = [
      '#type' => 'textfield',
      '#title' => t('PRINT NO'),
      '#size' => 10,
      '#default_value' => $args['printno'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
        ['value' => 'resolution'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['print-no'],
      ],
    ];

    $bill_years = [];
    $bill_years['0'] = 'Any';
    $years_option = [];
    foreach ($this->getSessionYearList() as $year) {
      $bill_years[$year] = $year . '-' . ($year + 1);
    }
    foreach ($this->getYearList() as $year) {
      $years_option[$year] = $year;
    }
    $form['session_year'] = [
      '#type' => 'select',
      '#title' => ('SESSION YEAR'),
      '#options' => $bill_years,
      '#default_value' => $args['session_year'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
        ['value' => 'resolution'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['session-year'],
      ],
    ];

    $form['month'] = [
      '#type' => 'select',
      '#title' => ('Month'),
      '#options' => ['all' => 'Any'] + $this->getMonthsOptions(),
      '#default_value' => $month_default,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'meeting'],
            ['value' => 'session'],
            ['value' => 'floor'],
            ['value' => 'public_hearing'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['month'],
      ],
    ];

    $form['year'] = [
      '#type' => 'select',
      '#title' => ('YEAR'),
      '#options' => $years_option,
      '#default_value' => $year_default,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'meeting'],
            ['value' => 'session'],
            ['value' => 'floor'],
            ['value' => 'public_hearing'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['year'],
      ],
    ];

    $form['full_text'] = [
      '#type' => 'textfield',
      '#title' => t('TITLE / SPONSOR MEMO / FULL TEXT'),
      '#default_value' => $args['full_text'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
        ['value' => 'resolution'],
        ['value' => 'floor'],
        ['value' => 'public_hearing'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['full-text'],
      ],
    ];

    $form['sponsor'] = [
      '#type' => 'select',
      '#title' => ('SENATE SPONSOR'),
      '#options' => ['all' => 'Any'] + $this->getSenatorsList(),
      '#default_value' => $args['sponsor'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
        ['value' => 'resolution'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['sponsor'],
      ],
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => t('BILL STATUS'),
      '#options' => [
        'all' => t('Any'),
        'INTRODUCED' => t('Introduced'),
        'IN_ASSEMBLY_COMM' => t('In Assembly Committee'),
        'IN_SENATE_COMM' => t('In Senate Committee'),
        'ASSEMBLY_FLOOR' => t('Assembly Floor Calendar'),
        'SENATE_FLOOR' => t('Senate Floor Calendar'),
        'PASSED_ASSEMBLY' => t('Passed Assembly'),
        'PASSED_SENATE' => t('Passed Senate'),
        'DELIVERED_TO_GOV' => t('Delivered to Governor'),
        'SIGNED_BY_GOV' => t('Signed by Governor'),
        'VETOED' => t('Vetoed'),
        'STRICKEN' => t('Stricken'),
        'LOST' => t('Lost'),
        'SUBSTITUTED' => t('Substituted'),
        'ADOPTED' => t('Adopted'),
      ],
      '#default_value' => $args['status'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['status'],
      ],
    ];

    $form['committee'] = [
      '#type' => 'select',
      '#title' => ('IN COMMITTEE'),
      '#options' => $this->getCommitteeList(),
      '#default_value' => $args['committee'] ?? NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
        ['value' => 'meeting'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['committee'],
      ],
    ];

    $form['issue'] = [
      '#type' => 'entity_autocomplete',
      '#title' => ('ISSUE'),
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['issues'],
      ],
      '#default_value' => !empty($args['issue']) ? Term::load($args['issue']) : NULL,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
        ['value' => 'bill'],
          ],
        ],
      ],
      '#attributes' => [
        'class' => ['issue'],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['#attached']['library'][] = 'nys_legislation_explorer/nys_legislation_explorer';
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
    $params = [];
    $date_range = '';
    if (!empty($values['month']) && !empty($values['year']) && $values['month'] !== 'all') {
      $year = $values['year'];
      $month = $values['month'];
      $first_day_month = date('Y-m-01', strtotime("$year-$month-01"));
      $last_day_month = date('Y-m-t', strtotime("$year-$month-01"));
      $date_range = $first_day_month . '--' . $last_day_month;
    }
    if (!empty($values['year']) && $values['month'] == 'all') {
      $year = $values['year'];
      $first_day_year = date('Y-m-d', strtotime("$year-01-01"));
      $last_day_year = date('Y-m-d', strtotime("$year-12-31"));
      $date_range = $first_day_year . '--' . $last_day_year;
    }
    switch ($values['type']) {
      case 'bill':
        $params = [
          'type' => $values['type'] ?: '',
          'session_year' => $values['session_year'] ?: '',
          'issue' => $values['issue'] ?: '',
          'printno' => $values['printno'] ?: '',
          'status' => $values['status'] ?: '',
          'sponsor' => $values['sponsor'] ?: '',
          'full_text' => $values['full_text'] ?: '',
          'committee' => $values['committee'] ?: '',
          'sort_by' => 'field_ol_last_status_date',
          'sort_order' => 'DESC',
        ];
        break;

      case 'resolution':
        $params = [
          'type' => $values['type'] ?: '',
          'session_year' => $values['session_year'] ?: '',
          'printno' => $values['printno'] ?: '',
          'sponsor' => $values['sponsor'] ?: '',
          'full_text' => $values['full_text'] ?: '',
          'sort_by' => 'field_ol_print_no',
          'sort_order' => 'DESC',
        ];
        break;

      case 'meeting':
        $params = [
          'type' => $values['type'] ?: '',
          'meeting_date' => $date_range ?: '',
          'committee' => $values['committee'] ?: '',
          'sort_by' => ' field_date_range',
          'sort_order' => 'DESC',
        ];
        break;

      case 'session':
        $params = [
          'type' => $values['type'] ?: '',
          'date' => $date_range ?: '',
          'sort_by' => 'field_date_range',
          'sort_order' => 'DESC',
        ];
        break;

      case 'floor':
      case 'public_hearing':
        $params = [
          'type' => 'transcript' ?: '',
          'transcript_type' => $values['type'] ?: '',
          'publish_date' => $date_range ?: '',
          'full_text' => $values['full_text'] ?: '',
          'sort_by' => 'field_ol_publish_date',
          'sort_order' => 'DESC',
        ];
        break;

      default:
        break;
    }

    // Filter out any empty values.
    $params = array_filter(
          $params, function ($value) {
              return $value !== '';
          }
      );

    $url = Url::fromRoute('nys_legislation_explorer.searchLegislation', [], ['query' => $params]);

    $form_state->setRedirectUrl($url);
  }

}
