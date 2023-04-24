<?php

namespace Drupal\nys_legislation_explorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * The Search advanced legislation form class.
 */
class SearchAdvancedLegislationForm extends FormBase {

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
    $months = [];
    for ($i = 0; $i < 12; $i++) {
      $time = strtotime(sprintf('%d months', $i));
      $months[$i] = date('F', $time);
    }
    return $months;
  }

  /**
   * Returns a list of Senators.
   *
   * @return array
   *   Senators array.
   */
  public function getSenatorsList(): array {
    $query = \Drupal::database()->select('taxonomy_term__field_senator_name', 's');
    $query->fields('s', ['entity_id']);
    $query->addExpression("CONCAT(s.field_senator_name_given,' ',s.field_senator_name_family)", 'senator_full_name');
    $query->orderBy('s.field_senator_name_family');

    $results = [];
    $results = $query->execute()->fetchAllKeyed(0, 1);
    $results['all'] = 'Any';
    ksort($results);
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
      $result[$committe->label()] = $committe->label();
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
        'transcript' => t('Session Transcripts'),
        'public_hearing' => t('Public Hearing Transcripts'),
      ],
    ];

    $form['printno'] = [
      '#type' => 'textfield',
      '#title' => t('PRINT NO'),
      '#size' => 10,
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
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'bill'],
            ['value' => 'resolution'],
          ],
        ],
      ],
    ];

    $form['session_year'] = [
      '#type' => 'select',
      '#title' => ('SESSION YEAR'),
      '#options' => $bill_years,
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
      '#options' => $this->getMonthsOptions(),
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'agenda'],
            ['value' => 'calendar'],
            ['value' => 'transcript'],
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
            ['value' => 'transcript'],
            ['value' => 'public_hearing'],
          ],
        ],
      ],
    ];

    $form['text_memo'] = [
      '#type' => 'textfield',
      '#title' => t('TITLE / SPONSOR MEMO / FULL TEXT'),
      '#states' => [
        'visible' => [
          'select[name="type"]' => [
            ['value' => 'bill'],
            ['value' => 'resolution'],
            ['value' => 'transcript'],
            ['value' => 'public_hearing'],
          ],
        ],
      ],
    ];

    $form['sponsor'] = [
      '#type' => 'select',
      '#title' => ('SENATE SPONSOR'),
      '#options' => $this->getSenatorsList(),
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

    $params = [
      'type' => $values['type'] ?: '',
      'session_year' => $values['session_year'] ?: '',
      'issue' => $values['status'] ?: '',
      'printno' => $values['printno'] ?: '',
      'status' => $values['status'] ?: '',
      'sponsor' => $values['sponsor'] ?: '',
      'title' => $values['text_memo'] ?: '',
      'memo' => $values['text_memo'] ?: '',
      'committee' => $values['committee'] ?: '',
      'year' => $values['year'] ?: '',
      'month' => $values['month'] ?: '',
    ];

    // Filter out any empty values.
    $params = array_filter($params, function ($value) {
      return $value !== '';
    });

    $url = Url::fromRoute('view.advanced_legislation_search.page_1', [], ['query' => $params]);
    $form_state->setRedirectUrl($url);
  }

}
