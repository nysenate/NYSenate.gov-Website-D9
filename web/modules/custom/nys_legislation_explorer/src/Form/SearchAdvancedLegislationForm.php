<?php

namespace Drupal\nys_legislation_explorer\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Search advanced legislation form class.
 */
class SearchAdvancedLegislationForm extends FormBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->requestStack = $container->get('request_stack');
    return $instance;
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
    $args = $this->requestStack->getCurrentRequest()->query->all();

    $form['my_block'] = [
      '#type' => 'block',
      '#attributes' => [
        'class' => ['my-custom-class'],
      ],
    ];

    $form['my_block']['my_block_text'] = [
      '#type' => 'item',
      '#markup' => $this->t('This is my custom block.'),
    ];

    $form['my_block']['my_block_title'] = [
      '#type' => 'item',
      '#markup' => $this->t('My Block Title'),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => ('FILTER BY CONTENT TYPE:'),
      '#options' => [
        'bill' => t('Bills'),
        'resolution' => t('Resolutions'),
        'agenda' => t('Committee Meeting Agendas'),
        'calendar' => t('Session Calendars'),
        'floor' => t('Session Transcripts'),
        'public_hearing' => t('Public Hearing Transcripts'),
      ],
      '#default_value' => $args['type'] ?? NULL,
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
    ];

    $bill_years = [];
    $bill_years['0'] = 'Any';
    foreach ($this->getSessionYearList() as $year) {
      $bill_years[$year] = $year . '-' . ($year + 1);
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
    ];

    $form['month'] = [
      '#type' => 'select',
      '#title' => ('Month'),
      '#options' => ['all' => 'Any'] + $this->getMonthsOptions(),
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'agenda'],
            ['value' => 'calendar'],
            ['value' => 'floor'],
            ['value' => 'public_hearing'],
          ],
        ],
      ],
    ];

    $form['year'] = [
      '#type' => 'select',
      '#title' => ('YEAR'),
      '#options' => $years_option,
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'agenda'],
            ['value' => 'calendar'],
            ['value' => 'floor'],
            ['value' => 'public_hearing'],
          ],
        ],
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
            ['value' => 'agenda'],
          ],
        ],
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
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('SEARCH'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $params = [];
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
        ];
        break;

      case 'resolution':
        $params = [
          'type' => $values['type'] ?: '',
          'session_year' => $values['session_year'] ?: '',
          'printno' => $values['printno'] ?: '',
          'sponsor' => $values['sponsor'] ?: '',
          'full_text' => $values['full_text'] ?: '',
        ];
        break;

      case 'agenda':
        $params = [
          'type' => $values['type'] ?: '',
          'meeting_date' => $date_range ?: '',
          'committee' => $values['committee'] ?: '',
        ];
        break;

      case 'calendar':
        $params = [
          'type' => $values['type'] ?: '',
          'date' => $date_range ?: '',
        ];
        break;

      case 'floor':
      case 'public_hearing':
        $params = [
          'type' => 'transcript' ?: '',
          'publish_date' => $date_range ?: '',
        ];
        break;

      default:
        break;
    }

    // Filter out any empty values.
    $params = array_filter($params, function ($value) {
      return $value !== '';
    });

    $url = Url::fromRoute('nys_legislation_explorer.searchLegislation', [], ['query' => $params]);

    $form_state->setRedirectUrl($url);
  }

}
